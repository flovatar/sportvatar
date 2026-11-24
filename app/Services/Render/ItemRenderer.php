<?php

namespace App\Services\Render;

use App\Models\Sportvatar;
use App\Models\SportvatarTemplate;
use App\Models\RenderedImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Renders one item to static images and uploads them to R2:
 *   {prefix}{type}/r/{flow_id}/{hash}/full.png | full.jpg | thumb.jpg | og.jpg   (+ latest.json one level up)
 * hash = content hash of the composed source SVG(s) + renderer version, so keys are immutable
 * (Cache-Control: 1 year, immutable) and a changed item simply gets a new folder. Reserved for later:
 * anim.gif / anim.mp4 in the same folder (animated exports need a browser; not done here).
 *
 * Sportvatars render from the stored on-chain SVG (Sportvatar::full_svg); templates from series prefix+svg+suffix.
 * Animations are frozen in their "settled" state (finite CSS animations at the end, e.g. combo
 * fade-ins visible; infinite ones and SMIL at their first frame).
 */
class ItemRenderer
{
    public const TYPES = ['sportvatar', 'template'];

    public array $timings = [];

    public function __construct(private SvgFlattener $flattener, private Rasterizer $rasterizer)
    {
    }

    /** @return array{full: string, nobg: ?string}|null composed SVG(s) exactly as the site shows them */
    public function source(string $type, int $flowId): ?array
    {
        $model = match ($type) {
            'sportvatar' => Sportvatar::where('flow_id', $flowId)->first(),
            'template' => SportvatarTemplate::where('flow_id', $flowId)->first(),
            default => throw new RuntimeException('unknown type '.$type),
        };
        if (! $model) {
            return null;
        }
        $full = (string) $model->full_svg;
        if (trim($full) === '') {
            return null;
        }
        $nobg = null; // Sportvatars have no background-less variant

        return ['full' => $full, 'nobg' => ($nobg !== null && trim($nobg) !== '' && $nobg !== $full) ? $nobg : null];
    }

    /** CSS @keyframes or SMIL in the composed SVG: a static render shows one frozen frame. */
    public static function isAnimated(string $svg): bool
    {
        return (bool) preg_match('/@(-webkit-)?keyframes|<animate|<set\b/i', $svg);
    }

    public static function hash(string $type, array $src): string
    {
        return substr(sha1(config('render.version').'|'.$type.'|'.$src['full'].'|'.($src['nobg'] ?? '')), 0, 12);
    }

    public static function basePath(string $type, int $flowId): string
    {
        $prefix = (string) config('render.prefix');
        if (config('services.flow.network') !== 'mainnet' && ! str_starts_with($prefix, 'testnet')) {
            throw new RuntimeException('Refusing to render a non-mainnet item outside the testnet/ prefix (RENDER_PREFIX)');
        }

        return $prefix.$type.'/r/'.$flowId;
    }

    /**
     * @return array{status: string, hash?: string, bytes?: int, ms?: int, error?: string}
     *   status: rendered | unchanged | missing | dry-run | error
     */
    public function render(string $type, int $flowId, bool $force = false, bool $dryRun = false): array
    {
        $t0 = microtime(true);
        $this->timings = [];
        $src = $this->source($type, $flowId);
        if (! $src) {
            return ['status' => 'missing'];
        }
        $hash = self::hash($type, $src);
        $record = RenderedImage::where('item_type', $type)->where('flow_id', $flowId)->first();
        if ($record && $record->hash === $hash && $record->source === 'browser' && $record->rendered_at && ! $dryRun) {
            // a browser (Chromium) render of this exact item state outranks the server render: never overwrite it
            return ['status' => 'unchanged', 'hash' => $hash];
        }
        if (! $force && $record && $record->hash === $hash && ! $record->error) {
            return ['status' => 'unchanged', 'hash' => $hash];
        }
        $base = self::basePath($type, $flowId);

        try {
            $files = $this->build($type, $src);
            if ($dryRun) {
                return ['status' => 'dry-run', 'hash' => $hash, 'bytes' => array_sum(array_map(fn ($f) => strlen($f[0]), $files)),
                    'ms' => (int) round((microtime(true) - $t0) * 1000), 'files' => $files];
            }
            $disk = Storage::disk(config('render.disk'));
            $cache = (string) config('render.cache_control');
            $t = microtime(true);
            foreach ($files as $name => [$bytes, $mime]) {
                $disk->put($base.'/'.$hash.'/'.$name, $bytes, ['ContentType' => $mime, 'CacheControl' => $cache]);
            }
            $variants = array_keys($files);
            $disk->put($base.'/latest.json', json_encode([
                'type' => $type, 'flow_id' => $flowId, 'hash' => $hash, 'variants' => $variants,
                'base' => config('render.public_url').'/'.$base.'/'.$hash.'/', 'rendered_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES), ['ContentType' => 'application/json', 'CacheControl' => 'public, max-age=60']);
            $this->timings['upload'] = (int) round((microtime(true) - $t) * 1000);

            $bytes = array_sum(array_map(fn ($f) => strlen($f[0]), $files));
            $ms = (int) round((microtime(true) - $t0) * 1000);
            RenderedImage::updateOrCreate(['item_type' => $type, 'flow_id' => $flowId], [
                'hash' => $hash, 'path' => $base.'/'.$hash, 'source' => 'server', 'animated' => self::isAnimated($src['full']),
                'variants' => $variants, 'bytes' => $bytes, 'render_ms' => $ms,
                'warnings' => count($this->flattener->warnings), 'error' => null, 'rendered_at' => now(),
            ]);

            return ['status' => 'rendered', 'hash' => $hash, 'bytes' => $bytes, 'ms' => $ms];
        } catch (Throwable $e) {
            if (! $dryRun) {
                RenderedImage::updateOrCreate(['item_type' => $type, 'flow_id' => $flowId], [
                    'hash' => $record->hash ?? $hash, 'path' => $record->path ?? $base.'/'.$hash, 'variants' => $record->variants ?? [],
                    'error' => mb_substr($e->getMessage(), 0, 1000), 'attempts' => ($record->attempts ?? 0) + 1,
                ]);
            }

            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /** @return array<string, array{0: string, 1: string}> variant => [bytes, mime] */
    public function build(string $type, array $src): array
    {
        $size = (int) config('render.size', 1000);
        $files = [];

        $t = microtime(true);
        $flat = $this->flattener->flatten($src['full']);
        $warnings = $this->flattener->warnings;
        $flatNobg = $src['nobg'] !== null ? $this->flattener->flatten($src['nobg']) : null;
        $this->flattener->warnings = array_merge($warnings, $this->flattener->warnings);
        $this->timings['flatten'] = (int) round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $png = $this->rasterizer->png($flat, $size, $size);
        $files['full.png'] = [$png, 'image/png'];
        if ($flatNobg !== null) {
            $files['nobg.png'] = [$this->rasterizer->png($flatNobg, $size, $size), 'image/png'];
        }
        $this->timings['resvg'] = (int) round((microtime(true) - $t) * 1000);

        $t = microtime(true);
        $gd = $this->gd($png);
        $files['full.jpg'] = [$this->jpeg($gd, $size), 'image/jpeg'];
        $files['thumb.jpg'] = [$this->jpeg($gd, (int) config('render.thumb', 400)), 'image/jpeg'];
        $files['og.jpg'] = [$this->og($gd), 'image/jpeg'];
        $this->timings['jpeg'] = (int) round((microtime(true) - $t) * 1000);

        return $files;
    }

    /** PNG -> JPEG on white (transparent items), resized to $size. GD: ~10x cheaper than Imagick here. */
    public function jpeg(\GdImage $src, int $size): string
    {
        $out = $this->canvas($size, $size);
        imagecopyresampled($out, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));

        return $this->toJpeg($out);
    }

    /** 1200x630 share card: the square render centred on a blurred, zoomed copy of itself (or white if transparent). */
    public function og(\GdImage $src): string
    {
        [$w, $h] = config('render.og', [1200, 630]);
        $sw = imagesx($src);
        $opaque = ((imagecolorat($src, 2, 2) >> 24) & 0x7F) === 0 && ((imagecolorat($src, $sw - 3, 2) >> 24) & 0x7F) === 0;
        $out = $this->canvas($w, $h);
        if ($opaque) {
            // backdrop: blur a 1/8-size copy (cheap), then scale it up 8x bilinearly - smooth, no banding
            $mw = (int) ($w / 8);
            $mh = (int) ($h / 8);
            $mid = imagecreatetruecolor($mw, $mh);
            imagecopyresampled($mid, $src, 0, 0, 0, (int) (($sw - $sw * $mh / $mw) / 2), $mw, $mh, $sw, (int) ($sw * $mh / $mw));
            for ($i = 0; $i < 8; $i++) {
                imagefilter($mid, IMG_FILTER_GAUSSIAN_BLUR);
            }
            imagefilter($mid, IMG_FILTER_BRIGHTNESS, -20);
            imagesetinterpolation($mid, IMG_BILINEAR_FIXED);
            $big = imagescale($mid, $w, $h, IMG_BILINEAR_FIXED);
            imagecopy($out, $big, 0, 0, 0, 0, $w, $h);
        }
        imagecopyresampled($out, $src, (int) (($w - $h) / 2), 0, 0, 0, $h, $h, $sw, imagesy($src));

        return $this->toJpeg($out);
    }

    private function gd(string $png): \GdImage
    {
        $im = imagecreatefromstring($png);
        if (! $im) {
            throw new RuntimeException('GD could not read the PNG');
        }
        imagealphablending($im, true);

        return $im;
    }

    private function canvas(int $w, int $h): \GdImage
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagealphablending($im, true);

        return $im;
    }

    private function toJpeg(\GdImage $im): string
    {
        imageinterlace($im, true);
        ob_start();
        imagejpeg($im, null, (int) config('render.jpeg_quality', 88));

        return (string) ob_get_clean();
    }
    /**
     * Register a browser (Chromium) render produced elsewhere (agent box) for the CURRENT item state.
     * $files: variant => local file path (full.png, full.jpg, nobg.png, thumb.jpg, og.jpg). Stored under
     * {base}/{hash}-b/ and preferred over the server render until the item changes (new hash).
     */
    public function importBrowserRender(string $type, int $flowId, array $files, ?string $expectHash = null): array
    {
        $src = $this->source($type, $flowId);
        if (! $src) {
            return ['status' => 'missing'];
        }
        $hash = self::hash($type, $src);
        if ($expectHash !== null && $expectHash !== $hash) {
            return ['status' => 'stale', 'error' => 'item changed since the browser render (current hash '.$hash.')', 'hash' => $hash];
        }
        $base = self::basePath($type, $flowId);
        $disk = Storage::disk(config('render.disk'));
        $mimes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'mp4' => 'video/mp4', 'webp' => 'image/webp'];
        $bytes = 0;
        foreach ($files as $name => $path) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! preg_match('/^[a-z0-9]+\.(png|jpg|gif|mp4|webp)$/', $name) || ! is_file($path)) {
                return ['status' => 'error', 'error' => 'bad variant '.$name];
            }
            $data = file_get_contents($path);
            $bytes += strlen($data);
            $disk->put($base.'/'.$hash.'-b/'.$name, $data, ['ContentType' => $mimes[$ext], 'CacheControl' => (string) config('render.cache_control')]);
        }
        RenderedImage::updateOrCreate(['item_type' => $type, 'flow_id' => $flowId], [
            'hash' => $hash, 'path' => $base.'/'.$hash.'-b', 'source' => 'browser', 'animated' => self::isAnimated($src['full']),
            'variants' => array_keys($files), 'bytes' => $bytes, 'error' => null, 'rendered_at' => now(),
        ]);

        return ['status' => 'imported', 'hash' => $hash, 'bytes' => $bytes];
    }
}
