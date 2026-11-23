<?php

namespace App\Services\Render;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Parser\Parser as CssParser;
use Throwable;

/**
 * Turns a browser-oriented SVG (CSS <style> blocks with sibling selectors, @keyframes, SMIL)
 * into a static SVG that resvg can rasterise exactly like a browser would show it "settled":
 *
 *  - Resolves the full CSS cascade itself (specificity, order, !important, inline style) with
 *    symfony/css-selector, so selectors resvg does not support (`~`, `+`, `[class$=..]`, `:not()`…)
 *    still apply. This is what makes the Flobit combos (`#TRIGGER ~ #TARGET …`) render. The result is
 *    written to each element's style attribute and the <style> elements are removed.
 *  - CSS animations are frozen: finite ones at their end state (fill-mode forwards/both, e.g. the
 *    combo fade-ins), infinite ones at the start of their active phase.
 *  - SMIL (<animate>, <animateTransform>, <set>) is frozen at t = 0.
 *  - CSS `transform` (px/deg units, transform-origin, transform-box) becomes an SVG matrix().
 *
 * The input must already be composed in SITE layer order (full_svg) - this class never reorders.
 */
class SvgFlattener
{
    private const SVG_NS = 'http://www.w3.org/2000/svg';

    /** Properties consumed here, never written to the output style attribute. */
    private const DROP = [
        'transform' => 1, 'transform-origin' => 1, 'transform-box' => 1, 'will-change' => 1, 'cursor' => 1,
        'transition' => 1, 'transition-property' => 1, 'transition-duration' => 1, 'transition-delay' => 1,
        'transition-timing-function' => 1, 'animation' => 1, 'animation-name' => 1, 'animation-duration' => 1,
        'animation-delay' => 1, 'animation-iteration-count' => 1, 'animation-direction' => 1,
        'animation-fill-mode' => 1, 'animation-timing-function' => 1, 'animation-play-state' => 1,
        'animation-composition' => 1, 'animation-timeline' => 1, '-webkit-animation' => 1,
    ];

    private const ANIM_LONGHANDS = ['animation-name', 'animation-duration', 'animation-timing-function', 'animation-delay',
        'animation-iteration-count', 'animation-direction', 'animation-fill-mode', 'animation-play-state'];

    public array $stats = [];
    public array $warnings = [];

    private DOMDocument $doc;
    private DOMXPath $xpath;
    /** @var DOMElement[] */
    private array $elements = [];
    private array $byId = [];
    private array $byClass = [];
    private array $byTag = [];
    private array $keyframes = [];
    /** element oid => prop => [value, important, specificity, order, animated] */
    private array $computed = [];
    private array $viewBox = [0.0, 0.0, 3000.0, 3000.0];

    private static ?CssSelectorConverter $converter = null;
    private static array $xpathCache = [];
    private static array $specCache = [];

    public function flatten(string $svg): string
    {
        $this->stats = ['rules' => 0, 'selectors' => 0, 'selectors_skipped' => 0, 'matched' => 0,
            'animated_elements' => 0, 'smil' => 0, 'css_transforms' => 0, 'bbox_origin_approx' => 0];
        $this->warnings = [];
        $this->computed = $this->byId = $this->byClass = $this->byTag = $this->elements = $this->keyframes = [];

        $this->load($svg);
        $this->index();
        $rules = $this->collectCss();
        $this->cascade($rules);
        $this->applyAnimations();
        $this->writeStyles();
        $this->freezeSmil();

        foreach (iterator_to_array($this->doc->getElementsByTagName('style')) as $style) {
            $style->parentNode->removeChild($style);
        }
        foreach (iterator_to_array($this->doc->getElementsByTagName('script')) as $script) {
            $script->parentNode->removeChild($script);
        }

        $out = $this->doc->saveXML($this->doc->documentElement);

        return preg_replace('/^<svg\b/', '<svg xmlns="'.self::SVG_NS.'"', $out, 1);
    }

    // ---------------------------------------------------------------- loading / indexing

    private function load(string $svg): void
    {
        // Work without the default namespace so XPath name tests (`path`, `g`) match.
        $svg = preg_replace('/\sxmlns\s*=\s*(["\'])http:\/\/www\.w3\.org\/2000\/svg\1/', '', $svg);
        $svg = preg_replace('/^\s*<\?xml[^>]*\?>/', '', $svg);
        if (str_contains($svg, 'xlink:') && !preg_match('/<svg\b[^>]*xmlns:xlink=/', $svg)) {
            $svg = preg_replace('/<svg\b/', '<svg xmlns:xlink="http://www.w3.org/1999/xlink"', $svg, 1);
        }

        $this->doc = new DOMDocument();
        $this->doc->preserveWhiteSpace = true;
        $prev = libxml_use_internal_errors(true);
        $ok = $this->doc->loadXML($svg, LIBXML_PARSEHUGE | LIBXML_NONET | LIBXML_COMPACT);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok || !$this->doc->documentElement) {
            $first = $errors[0] ?? null;
            throw new RuntimeException('Invalid SVG'.($first ? ': '.trim($first->message).' line '.$first->line : ''));
        }
        $this->xpath = new DOMXPath($this->doc);

        $vb = $this->doc->documentElement->getAttribute('viewBox');
        if ($vb && count($p = preg_split('/[\s,]+/', trim($vb))) === 4) {
            $this->viewBox = array_map('floatval', $p);
        }
    }

    private function index(): void
    {
        foreach ($this->doc->getElementsByTagName('*') as $el) {
            $oid = spl_object_id($el);
            $this->elements[$oid] = $el;
            $this->byTag[$el->localName][] = $el;
            if (($id = $el->getAttribute('id')) !== '') {
                $this->byId[$id][] = $el;
            }
            if (($class = trim($el->getAttribute('class'))) !== '') {
                foreach (array_unique(preg_split('/\s+/', $class)) as $c) {
                    $this->byClass[$c][] = $el;
                }
            }
        }
    }

    // ---------------------------------------------------------------- CSS parsing

    /** @return array<int, array{selectors: string, decls: array}> in document order */
    private function collectCss(): array
    {
        $rules = [];
        foreach ($this->doc->getElementsByTagName('style') as $style) {
            $media = strtolower(trim($style->getAttribute('media')));
            if ($media !== '' && !in_array($media, ['all', 'screen'], true)) {
                continue;
            }
            foreach ($this->parseStylesheet($style->textContent) as $rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    private function parseStylesheet(string $css): array
    {
        $css = preg_replace('~/\*.*?\*/~s', '', $css);
        $css = str_replace(['<!--', '-->', '<![CDATA[', ']]>'], ' ', $css);
        $rules = [];
        $len = strlen($css);
        $i = 0;
        while ($i < $len) {
            $i += strspn($css, " \t\r\n;", $i);
            if ($i >= $len) {
                break;
            }
            $open = $this->findTopLevel($css, $i, '{;');
            if ($open === null) {
                break;
            }
            $prelude = trim(substr($css, $i, $open - $i));
            if ($css[$open] === ';') { // @import / @charset / stray
                $i = $open + 1;
                continue;
            }
            $close = $this->matchBrace($css, $open);
            $body = substr($css, $open + 1, $close - $open - 1);
            $i = $close + 1;

            if ($prelude !== '' && $prelude[0] === '@') {
                if (preg_match('/^@(?:-[a-z]+-)?keyframes\s+([^\s{]+)/i', $prelude, $m)) {
                    $this->keyframes[trim($m[1], '"\'')] = $this->parseKeyframes($body);
                } elseif (preg_match('/^@media\s+(all|screen)\b/i', $prelude)) {
                    foreach ($this->parseStylesheet($body) as $r) {
                        $rules[] = $r;
                    }
                }
                continue; // @font-face, @supports, print media…
            }
            if ($prelude === '') {
                continue;
            }
            $rules[] = ['selectors' => $prelude, 'decls' => $this->parseDeclarations($body)];
        }

        return $rules;
    }

    private function parseKeyframes(string $body): array
    {
        $frames = [];
        $len = strlen($body);
        $i = 0;
        while ($i < $len) {
            $open = strpos($body, '{', $i);
            if ($open === false) {
                break;
            }
            $close = $this->matchBrace($body, $open);
            $sel = strtolower(trim(substr($body, $i, $open - $i)));
            $decls = $this->parseDeclarations(substr($body, $open + 1, $close - $open - 1));
            $i = $close + 1;
            foreach (explode(',', $sel) as $s) {
                $s = trim($s);
                $off = $s === 'from' ? 0.0 : ($s === 'to' ? 1.0 : (str_ends_with($s, '%') ? ((float) $s) / 100 : null));
                if ($off === null) {
                    continue;
                }
                $key = (string) round($off, 6);
                $frames[$key] = array_merge($frames[$key] ?? [], $decls);
            }
        }
        ksort($frames, SORT_NUMERIC);

        return $frames;
    }

    /** @return array<string, array{0: string, 1: bool}> property => [value, important] (later wins) */
    private function parseDeclarations(string $body): array
    {
        $decls = [];
        foreach ($this->splitTopLevel($body, ';') as $d) {
            $c = strpos($d, ':');
            if ($c === false) {
                continue;
            }
            $prop = strtolower(trim(substr($d, 0, $c)));
            $val = trim(substr($d, $c + 1));
            if ($prop === '' || $val === '') {
                continue;
            }
            $important = false;
            if (preg_match('/!\s*important\s*$/i', $val)) {
                $important = true;
                $val = trim(preg_replace('/!\s*important\s*$/i', '', $val));
            }
            if ($prop === 'animation' || $prop === '-webkit-animation') {
                foreach ($this->expandAnimation($val) as $p => $v) {
                    unset($decls[$p]);
                    $decls[$p] = [$v, $important];
                }
                continue;
            }
            if (str_starts_with($prop, '-webkit-animation-')) {
                $prop = substr($prop, 8);
            }
            unset($decls[$prop]);
            $decls[$prop] = [$val, $important];
        }

        return $decls;
    }

    /** `animation` shorthand → comma-joined longhand lists. */
    private function expandAnimation(string $val): array
    {
        $out = array_fill_keys(self::ANIM_LONGHANDS, []);
        foreach ($this->splitTopLevel($val, ',') as $one) {
            $a = ['animation-name' => 'none', 'animation-duration' => '0s', 'animation-timing-function' => 'ease',
                'animation-delay' => '0s', 'animation-iteration-count' => '1', 'animation-direction' => 'normal',
                'animation-fill-mode' => 'none', 'animation-play-state' => 'running'];
            $times = 0;
            foreach ($this->splitTopLevel(trim($one), ' ') as $tok) {
                $t = strtolower($tok);
                if (preg_match('/^-?[\d.]+m?s$/', $t)) {
                    $a[$times++ === 0 ? 'animation-duration' : 'animation-delay'] = $t;
                } elseif ($t === 'infinite' || preg_match('/^[\d.]+$/', $t)) {
                    $a['animation-iteration-count'] = $t;
                } elseif (in_array($t, ['normal', 'reverse', 'alternate', 'alternate-reverse'], true)) {
                    $a['animation-direction'] = $t;
                } elseif (in_array($t, ['none', 'forwards', 'backwards', 'both'], true) && $a['animation-name'] !== 'none' || in_array($t, ['forwards', 'backwards', 'both'], true)) {
                    $a['animation-fill-mode'] = $t;
                } elseif (in_array($t, ['running', 'paused'], true)) {
                    $a['animation-play-state'] = $t;
                } elseif (in_array($t, ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'step-start', 'step-end'], true)
                    || str_starts_with($t, 'cubic-bezier(') || str_starts_with($t, 'steps(')) {
                    $a['animation-timing-function'] = $t;
                } elseif ($t !== '') {
                    $a['animation-name'] = trim($tok, '"\'');
                }
            }
            foreach ($a as $k => $v) {
                $out[$k][] = $v;
            }
        }

        return array_map(fn ($l) => implode(', ', $l), $out);
    }

    private function findTopLevel(string $s, int $from, string $chars): ?int
    {
        $depth = 0;
        $quote = null;
        $len = strlen($s);
        for ($i = $from; $i < $len; $i++) {
            $ch = $s[$i];
            if ($quote !== null) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
            } elseif ($ch === '(' || $ch === '[') {
                $depth++;
            } elseif ($ch === ')' || $ch === ']') {
                $depth = max(0, $depth - 1);
            } elseif ($depth === 0 && str_contains($chars, $ch)) {
                return $i;
            }
        }

        return null;
    }

    private function matchBrace(string $s, int $open): int
    {
        $depth = 0;
        $quote = null;
        $len = strlen($s);
        for ($i = $open; $i < $len; $i++) {
            $ch = $s[$i];
            if ($quote !== null) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
            } elseif ($ch === '{') {
                $depth++;
            } elseif ($ch === '}' && --$depth === 0) {
                return $i;
            }
        }

        return $len;
    }

    /** Split on $sep outside quotes/parens/brackets (sep ' ' = any whitespace run). */
    private function splitTopLevel(string $s, string $sep): array
    {
        $parts = [];
        $buf = '';
        $depth = 0;
        $quote = null;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            if ($quote !== null) {
                $buf .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {
                    $buf .= $s[++$i];
                } elseif ($ch === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
            } elseif ($ch === '(' || $ch === '[') {
                $depth++;
            } elseif ($ch === ')' || $ch === ']') {
                $depth = max(0, $depth - 1);
            }
            $isSep = $depth === 0 && ($sep === ' ' ? ctype_space($ch) : $ch === $sep);
            if ($isSep) {
                if (trim($buf) !== '') {
                    $parts[] = trim($buf);
                }
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if (trim($buf) !== '') {
            $parts[] = trim($buf);
        }

        return $parts;
    }

    // ---------------------------------------------------------------- cascade

    private function cascade(array $rules): void
    {
        $order = 0;
        foreach ($rules as $rule) {
            $this->stats['rules']++;
            if (!$rule['decls']) {
                continue;
            }
            foreach ($this->splitTopLevel($rule['selectors'], ',') as $selector) {
                $order++;
                $this->stats['selectors']++;
                $matches = $this->match($selector);
                if ($matches === null) {
                    $this->stats['selectors_skipped']++;
                    continue;
                }
                $spec = $this->specificity($selector);
                foreach ($matches as $el) {
                    $this->stats['matched']++;
                    $oid = spl_object_id($el);
                    foreach ($rule['decls'] as $prop => [$val, $imp]) {
                        $this->offer($oid, $prop, $val, $imp, $spec, $order);
                    }
                }
            }
        }

        // inline style attribute: beats every selector (same importance level)
        foreach ($this->elements as $oid => $el) {
            if (($style = $el->getAttribute('style')) === '') {
                continue;
            }
            foreach ($this->parseDeclarations($style) as $prop => [$val, $imp]) {
                $this->offer($oid, $prop, $val, $imp, PHP_INT_MAX, PHP_INT_MAX);
            }
        }
    }

    private function offer(int $oid, string $prop, string $val, bool $imp, int $spec, int $order): void
    {
        $cur = $this->computed[$oid][$prop] ?? null;
        if ($cur === null || [$imp ? 1 : 0, $spec, $order] >= [$cur[1] ? 1 : 0, $cur[2], $cur[3]]) {
            $this->computed[$oid][$prop] = [$val, $imp, $spec, $order, false];
        }
    }

    /** @return DOMElement[]|null null = selector can never match statically (pseudo-classes) or is invalid */
    private function match(string $selector): ?array
    {
        $selector = trim($selector);
        // fast path: [tag|*][#id][.class…]
        if (preg_match('/^(\*|[A-Za-z][\w-]*)?((?:[#.][A-Za-z_\-][\w\-]*)*)$/', $selector, $m) && $selector !== '') {
            $tag = ($m[1] ?? '') === '*' ? '' : ($m[1] ?? '');
            preg_match_all('/([#.])([\w\-]+)/', $m[2], $parts, PREG_SET_ORDER);
            $ids = $classes = [];
            foreach ($parts as $p) {
                $p[1] === '#' ? $ids[] = $p[2] : $classes[] = $p[2];
            }
            if ($ids) {
                $cand = $this->byId[$ids[0]] ?? [];
            } elseif ($classes) {
                $cand = $this->byClass[$classes[0]] ?? [];
            } elseif ($tag !== '') {
                $cand = $this->byTag[$tag] ?? [];
            } else {
                $cand = array_values($this->elements);
            }
            if (count($ids) > 1 || ($ids && $classes) || count($classes) > 1 || ($tag !== '' && ($ids || $classes))) {
                $cand = array_values(array_filter($cand, function (DOMElement $el) use ($tag, $ids, $classes) {
                    if ($tag !== '' && $el->localName !== $tag) {
                        return false;
                    }
                    foreach ($ids as $id) {
                        if ($el->getAttribute('id') !== $id) {
                            return false;
                        }
                    }
                    $cls = preg_split('/\s+/', trim($el->getAttribute('class')));
                    foreach ($classes as $c) {
                        if (!in_array($c, $cls, true)) {
                            return false;
                        }
                    }

                    return true;
                }));
            }

            return $cand;
        }

        if (!array_key_exists($selector, self::$xpathCache)) {
            try {
                self::$converter ??= new CssSelectorConverter(false);
                self::$xpathCache[$selector] = self::$converter->toXPath($selector, 'descendant-or-self::');
            } catch (Throwable $e) {
                self::$xpathCache[$selector] = null;
            }
        }
        $xp = self::$xpathCache[$selector];
        if ($xp === null) {
            return null;
        }
        $nodes = @$this->xpath->query($xp, $this->doc);
        if ($nodes === false) {
            return null;
        }
        $out = [];
        foreach ($nodes as $n) {
            if ($n instanceof DOMElement) {
                $out[] = $n;
            }
        }

        return $out;
    }

    private function specificity(string $selector): int
    {
        if (isset(self::$specCache[$selector])) {
            return self::$specCache[$selector];
        }
        $value = 0;
        try {
            foreach ((new CssParser())->parse($selector) as $node) {
                $spec = $node->getSpecificity();
                [$a, $b, $c] = (fn () => [$this->a, $this->b, $this->c])->call($spec);
                $value = max($value, $a * 1000000 + $b * 1000 + $c);
            }
        } catch (Throwable $e) {
            $value = 0;
        }

        return self::$specCache[$selector] = $value;
    }

    // ---------------------------------------------------------------- CSS animations

    private function applyAnimations(): void
    {
        foreach ($this->computed as $oid => $props) {
            $names = isset($props['animation-name']) ? $this->splitTopLevel($props['animation-name'][0], ',') : [];
            if (!$names || (count($names) === 1 && strtolower($names[0]) === 'none')) {
                continue;
            }
            $list = fn (string $p, string $def) => isset($props[$p]) ? $this->splitTopLevel($props[$p][0], ',') : [$def];
            $durations = $list('animation-duration', '0s');
            $delays = $list('animation-delay', '0s');
            $counts = $list('animation-iteration-count', '1');
            $directions = $list('animation-direction', 'normal');
            $fills = $list('animation-fill-mode', 'none');

            $overrides = [];
            foreach ($names as $i => $name) {
                $name = trim($name, '"\' ');
                if (strtolower($name) === 'none' || !isset($this->keyframes[$name])) {
                    continue;
                }
                $dur = $this->seconds($durations[$i % count($durations)]);
                $delay = $this->seconds($delays[$i % count($delays)]);
                $countRaw = strtolower(trim($counts[$i % count($counts)]));
                $count = $countRaw === 'infinite' ? INF : max(0.0, (float) $countRaw);
                $dir = strtolower(trim($directions[$i % count($directions)]));
                $fill = strtolower(trim($fills[$i % count($fills)]));

                if (is_infinite($count)) {
                    // start of the active phase (t = max(delay, 0))
                    $iter = 0;
                    $progress = 0.0;
                    if ($delay < 0 && $dur > 0) {
                        $elapsed = -$delay / $dur;
                        $iter = (int) floor($elapsed);
                        $progress = $elapsed - $iter;
                    }
                } else {
                    if (!in_array($fill, ['forwards', 'both'], true)) {
                        continue; // effect is gone once the animation has finished
                    }
                    if ($count == 0.0) {
                        $iter = 0;
                        $progress = 0.0;
                    } elseif ($count == floor($count)) {
                        $iter = (int) $count - 1;
                        $progress = 1.0;
                    } else {
                        $iter = (int) floor($count);
                        $progress = $count - $iter;
                    }
                }
                $reverse = match ($dir) {
                    'reverse' => true,
                    'alternate' => $iter % 2 === 1,
                    'alternate-reverse' => $iter % 2 === 0,
                    default => false,
                };
                if ($reverse) {
                    $progress = 1.0 - $progress;
                }
                foreach ($this->keyframeValues($this->keyframes[$name], $progress) as $p => $v) {
                    $overrides[$p] = $v;
                }
            }
            if (!$overrides) {
                continue;
            }
            $this->stats['animated_elements']++;
            foreach ($overrides as $p => $v) {
                if (isset(self::DROP[$p]) && $p !== 'transform' && $p !== 'transform-origin') {
                    continue;
                }
                if (($this->computed[$oid][$p][1] ?? false) === true) {
                    continue; // !important beats animations
                }
                $this->computed[$oid][$p] = [$v, false, PHP_INT_MAX, PHP_INT_MAX, true];
            }
        }
    }

    /** Values of the keyframe at $progress (step: the last keyframe at or before progress). */
    private function keyframeValues(array $frames, float $progress): array
    {
        $result = [];
        $props = [];
        foreach ($frames as $decls) {
            foreach ($decls as $p => $_) {
                $props[$p] = true;
            }
        }
        foreach (array_keys($props) as $p) {
            if ($p === 'animation-timing-function') {
                continue;
            }
            $val = null;
            $has0 = false;
            foreach ($frames as $off => $decls) {
                if ((float) $off <= $progress + 1e-9 && isset($decls[$p])) {
                    $val = $decls[$p][0];
                }
                if ((float) $off === 0.0 && isset($decls[$p])) {
                    $has0 = true;
                }
            }
            if ($progress >= 1.0 - 1e-9) {
                // implicit 100% keyframe = underlying value
                $val = isset($frames['1'][$p]) ? $frames['1'][$p][0] : null;
            } elseif ($progress <= 1e-9 && !$has0) {
                $val = null; // implicit 0% keyframe = underlying value
            }
            if ($val !== null) {
                $result[$p] = $val;
            }
        }

        return $result;
    }

    private function seconds(string $v): float
    {
        $v = strtolower(trim($v));
        if (str_ends_with($v, 'ms')) {
            return ((float) $v) / 1000;
        }

        return (float) $v;
    }

    // ---------------------------------------------------------------- output

    private function writeStyles(): void
    {
        foreach ($this->computed as $oid => $props) {
            $el = $this->elements[$oid];
            $parts = [];
            foreach ($props as $p => [$v]) {
                if (isset(self::DROP[$p]) || str_starts_with($p, '-webkit-') || str_starts_with($p, '-moz-')) {
                    continue;
                }
                $parts[] = $p.':'.$v;
            }
            if ($parts) {
                $el->setAttribute('style', implode(';', $parts));
            } elseif ($el->hasAttribute('style')) {
                $el->removeAttribute('style');
            }

            $this->applyTransform($el, $props);
        }
    }

    private function applyTransform(DOMElement $el, array $props): void
    {
        $css = isset($props['transform']) ? trim($props['transform'][0]) : null;
        $originSet = isset($props['transform-origin']);
        if ($css === null && !$originSet) {
            return;
        }
        if ($css === null) {
            // transform-origin also applies to the transform *attribute* (it is the transform property)
            $attr = trim($el->getAttribute('transform'));
            if ($attr === '') {
                return;
            }
            $m = $this->parseTransformList($attr, false);
        } else {
            $this->stats['css_transforms']++;
            if (strtolower($css) === 'none') {
                $el->removeAttribute('transform');

                return;
            }
            $m = $this->parseTransformList($css, true);
        }
        if ($m === null) {
            $this->warnings[] = 'unsupported transform on #'.$el->getAttribute('id').': '.($css ?? $el->getAttribute('transform'));

            return;
        }
        if (!($m[0] == 1.0 && $m[1] == 0.0 && $m[2] == 0.0 && $m[3] == 1.0)) { // origin only matters for non-translations
            [$ox, $oy] = $this->origin($el, $props);
            if ($ox != 0.0 || $oy != 0.0) {
                $m = $this->mul($this->mul([1, 0, 0, 1, $ox, $oy], $m), [1, 0, 0, 1, -$ox, -$oy]);
            }
        }
        if ($this->isIdentity($m)) {
            $el->removeAttribute('transform');
        } else {
            $el->setAttribute('transform', 'matrix('.implode(' ', array_map(fn ($x) => $this->num($x), $m)).')');
        }
    }

    private function origin(DOMElement $el, array $props): array
    {
        $raw = strtolower(trim($props['transform-origin'][0] ?? '0 0'));
        $box = strtolower(trim($props['transform-box'][0] ?? 'view-box'));
        $tokens = preg_split('/\s+/', $raw);
        // keyword normalisation: allow "bottom right", "center", "top" …
        $x = $y = null;
        foreach ($tokens as $t) {
            if (in_array($t, ['left', 'right'], true)) {
                $x = $t === 'left' ? '0%' : '100%';
            } elseif (in_array($t, ['top', 'bottom'], true)) {
                $y = $t === 'top' ? '0%' : '100%';
            } elseif ($t === 'center') {
                if ($x === null) {
                    $x = '50%';
                } else {
                    $y ??= '50%';
                }
            } elseif ($x === null) {
                $x = $t;
            } elseif ($y === null) {
                $y = $t;
            }
        }
        $x ??= '50%';
        $y ??= '50%';
        if (count($tokens) === 1 && !in_array($tokens[0], ['top', 'bottom', 'left', 'right', 'center'], true)) {
            $y = '50%';
        }

        $bx = $by = 0.0;
        $bw = $this->viewBox[2];
        $bh = $this->viewBox[3];
        if ($box === 'fill-box' || $box === 'stroke-box' || $box === 'border-box' || $box === 'content-box') {
            $bbox = $this->bbox($el);
            if ($bbox === null) {
                $this->stats['bbox_origin_approx']++;
                $this->warnings[] = 'no bbox for #'.$el->getAttribute('id').' (transform-origin approximated)';

                return [0.0, 0.0];
            }
            [$bx, $by, $bw, $bh] = $bbox;
        } else {
            $bx = $this->viewBox[0];
            $by = $this->viewBox[1];
        }
        $len = fn (string $v, float $start, float $size) => str_ends_with($v, '%') ? $start + $size * ((float) $v) / 100 : ($box === 'view-box' ? (float) $v : $start + (float) $v);

        return [$len($x, $bx, $bw), $len($y, $by, $bh)];
    }

    /** @var callable|null fn(DOMElement): ?array [x, y, w, h] in the element's user space */
    public $bboxResolver = null;

    private function bbox(DOMElement $el): ?array
    {
        return $this->bboxResolver ? ($this->bboxResolver)($el, $this->doc) : null;
    }

    /** Parse a CSS (units) or SVG transform list to a matrix [a b c d e f]; null if unsupported. */
    private function parseTransformList(string $v, bool $css): ?array
    {
        if (!preg_match_all('/([a-zA-Z0-9]+)\s*\(([^)]*)\)/', $v, $fns, PREG_SET_ORDER)) {
            return null;
        }
        $m = [1, 0, 0, 1, 0, 0];
        foreach ($fns as [, $name, $args]) {
            $a = preg_split('/[\s,]+/', trim($args), -1, PREG_SPLIT_NO_EMPTY);
            $len = function ($x) {
                if (str_ends_with($x, '%')) {
                    throw new RuntimeException('percent');
                }

                return (float) $x; // px or unitless
            };
            $ang = function ($x) {
                $x = strtolower($x);
                if (str_ends_with($x, 'grad')) {
                    return ((float) $x) * 0.9;
                }
                if (str_ends_with($x, 'rad')) {
                    return rad2deg((float) $x);
                }
                if (str_ends_with($x, 'turn')) {
                    return ((float) $x) * 360;
                }

                return (float) $x; // deg or unitless (SVG)
            };
            try {
                $name = strtolower($name);
                $t = match ($name) {
                    'translate', 'translate3d' => [1, 0, 0, 1, $len($a[0] ?? 0), $len($a[1] ?? 0)],
                    'translatex' => [1, 0, 0, 1, $len($a[0] ?? 0), 0],
                    'translatey' => [1, 0, 0, 1, 0, $len($a[0] ?? 0)],
                    'translatez' => [1, 0, 0, 1, 0, 0],
                    'scale', 'scale3d' => [(float) ($a[0] ?? 1), 0, 0, (float) ($a[1] ?? $a[0] ?? 1), 0, 0],
                    'scalex' => [(float) ($a[0] ?? 1), 0, 0, 1, 0, 0],
                    'scaley' => [1, 0, 0, (float) ($a[0] ?? 1), 0, 0],
                    'scalez' => [1, 0, 0, 1, 0, 0],
                    'rotate', 'rotatez' => $this->rotate($ang($a[0] ?? 0), $css ? null : (isset($a[1]) ? [(float) $a[1], (float) ($a[2] ?? 0)] : null)),
                    // orthographic projection of 3D rotations (no perspective)
                    'rotatex' => [1, 0, 0, cos(deg2rad($ang($a[0] ?? 0))), 0, 0],
                    'rotatey' => [cos(deg2rad($ang($a[0] ?? 0))), 0, 0, 1, 0, 0],
                    'skewx' => [1, 0, tan(deg2rad($ang($a[0] ?? 0))), 1, 0, 0],
                    'skewy' => [1, tan(deg2rad($ang($a[0] ?? 0))), 0, 1, 0, 0],
                    'skew' => [1, tan(deg2rad($ang($a[1] ?? 0))), tan(deg2rad($ang($a[0] ?? 0))), 1, 0, 0],
                    'matrix' => count($a) === 6 ? array_map('floatval', $a) : throw new RuntimeException('matrix'),
                    default => throw new RuntimeException('fn '.$name),
                };
            } catch (Throwable $e) {
                return null;
            }
            $m = $this->mul($m, $t);
        }

        return $m;
    }

    private function rotate(float $deg, ?array $c): array
    {
        $r = deg2rad($deg);
        $m = [cos($r), sin($r), -sin($r), cos($r), 0, 0];
        if ($c) {
            $m = $this->mul($this->mul([1, 0, 0, 1, $c[0], $c[1]], $m), [1, 0, 0, 1, -$c[0], -$c[1]]);
        }

        return $m;
    }

    private function mul(array $p, array $q): array
    {
        return [
            $p[0] * $q[0] + $p[2] * $q[1],
            $p[1] * $q[0] + $p[3] * $q[1],
            $p[0] * $q[2] + $p[2] * $q[3],
            $p[1] * $q[2] + $p[3] * $q[3],
            $p[0] * $q[4] + $p[2] * $q[5] + $p[4],
            $p[1] * $q[4] + $p[3] * $q[5] + $p[5],
        ];
    }

    private function isIdentity(array $m): bool
    {
        return abs($m[0] - 1) < 1e-9 && abs($m[1]) < 1e-9 && abs($m[2]) < 1e-9 && abs($m[3] - 1) < 1e-9
            && abs($m[4]) < 1e-9 && abs($m[5]) < 1e-9;
    }

    private function num(float $x): string
    {
        $s = rtrim(rtrim(sprintf('%.6F', $x), '0'), '.');

        return $s === '-0' ? '0' : $s;
    }

    // ---------------------------------------------------------------- SMIL

    private function freezeSmil(): void
    {
        // document order (matters for additive animateTransform)
        $anims = iterator_to_array($this->xpath->query('//*[local-name()="animate" or local-name()="animateTransform" or local-name()="set" or local-name()="animateMotion" or local-name()="animateColor"]'));
        $animatedTransform = [];
        foreach ($anims as $a) {
            $this->stats['smil']++;
            $target = $a->parentNode;
            $href = $a->getAttribute('href') ?: $a->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
            if ($href !== '' && $href[0] === '#') {
                $target = ($this->byId[substr($href, 1)][0] ?? null);
            }
            if (!$target instanceof DOMElement || !$this->activeAtZero($a->getAttribute('begin'))) {
                continue;
            }
            $attr = $a->getAttribute('attributeName');
            $tag = $a->localName;
            if ($tag === 'animateMotion' || $attr === '') {
                continue;
            }
            if ($tag === 'set') {
                $value = $a->getAttribute('to');
            } else {
                $values = trim($a->getAttribute('values'));
                $value = $values !== '' ? trim(explode(';', $values)[0]) : ($a->hasAttribute('from') ? $a->getAttribute('from') : null);
            }
            if ($value === null || $value === '') {
                continue; // to-/by-animation: at t=0 the value is the base value
            }
            if ($tag === 'animateTransform') {
                $type = $a->getAttribute('type') ?: 'translate';
                $fn = $type.'('.$value.')';
                $oid = spl_object_id($target);
                $base = $animatedTransform[$oid] ?? trim($target->getAttribute('transform'));
                $animatedTransform[$oid] = $a->getAttribute('additive') === 'sum' && $base !== '' ? $base.' '.$fn : $fn;
                $target->setAttribute('transform', $animatedTransform[$oid]);
            } else {
                $target->setAttribute($attr, $value);
            }
        }
        foreach ($anims as $a) {
            if ($a->parentNode) {
                $a->parentNode->removeChild($a);
            }
        }
    }

    private function activeAtZero(string $begin): bool
    {
        $begin = trim($begin);
        if ($begin === '') {
            return true;
        }
        foreach (explode(';', $begin) as $b) {
            $b = trim($b);
            if (preg_match('/^-?[\d.]+(ms|s|min|h)?$/', $b) && $this->seconds($b) <= 0) {
                return true;
            }
        }

        return false;
    }
}
