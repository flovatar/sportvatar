<?php

namespace Tests\Unit;

use App\Services\Render\SvgFlattener;
use PHPUnit\Framework\TestCase;

class SvgFlattenerTest extends TestCase
{
    private function flat(string $body, string $css): \DOMXPath
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><style>'.$css.'</style>'.$body.'</svg>';
        $out = (new SvgFlattener())->flatten($svg);
        $this->assertStringNotContainsString('<style', $out);
        $doc = new \DOMDocument();
        $doc->loadXML($out);
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('s', 'http://www.w3.org/2000/svg');

        return $xp;
    }

    private function style(\DOMXPath $xp, string $id): string
    {
        return (string) $xp->query('//*[@id="'.$id.'"]')->item(0)?->getAttribute('style');
    }

    public function test_sibling_combo_with_forwards_animation_ends_visible(): void
    {
        $css = '#target{opacity:0} #trigger ~ #target{animation:fadeIn 1s 0.5s 1 forwards} @keyframes fadeIn{from{opacity:0}to{opacity:1}}';
        // trigger BEFORE target (site layer order): combo fires
        $xp = $this->flat('<g id="trigger"/><g id="target"><rect width="10" height="10"/></g>', $css);
        $this->assertStringContainsString('opacity:1', $this->style($xp, 'target'));
        // trigger AFTER target (contract order): `~` does not match, target stays hidden
        $xp = $this->flat('<g id="target"><rect width="10" height="10"/></g><g id="trigger"/>', $css);
        $this->assertStringContainsString('opacity:0', $this->style($xp, 'target'));
    }

    public function test_specificity_and_display_toggle(): void
    {
        $css = '#a ~ g .hide{display:none} .hide{display:inline} g.x{fill:red} #y{fill:blue}';
        $xp = $this->flat('<g id="a"/><g><path class="hide" id="p" d="M0 0h1"/></g><g class="x" id="y"/>', $css);
        $this->assertStringContainsString('display:none', $this->style($xp, 'p'));
        $this->assertStringContainsString('fill:blue', $this->style($xp, 'y'));
    }

    public function test_infinite_animation_frozen_at_first_frame_and_smil_removed(): void
    {
        $css = '#b{animation:blink 2s infinite} @keyframes blink{0%{opacity:.5}50%{opacity:1}}';
        $xp = $this->flat('<g id="b"/><circle id="c" r="1"><animate attributeName="r" values="5;9;5" dur="1s"/></circle>', $css);
        $this->assertStringContainsString('opacity:.5', $this->style($xp, 'b'));
        $this->assertSame('5', $xp->query('//*[@id="c"]')->item(0)->getAttribute('r'));
        $this->assertSame(0, $xp->query('//*[local-name()="animate"]')->length);
    }
}
