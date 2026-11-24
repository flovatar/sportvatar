<?php

namespace App\Support;

use App\Models\RenderedImage;
use Throwable;

/**
 * Public URLs of item images. New renders (R2, immutable hashed keys) win; otherwise the legacy CDN
 * export (prod only: RENDER_LEGACY_FALLBACK) - testnet never points at the prod CDN.
 */
class ItemImages
{
    private static array $memo = [];

    public static function record(string $type, $flowId): ?RenderedImage
    {
        if (! is_numeric($flowId)) {
            return null;
        }
        $key = $type.':'.(int) $flowId;
        if (! array_key_exists($key, self::$memo)) {
            try {
                self::$memo[$key] = RenderedImage::where('item_type', $type)->where('flow_id', (int) $flowId)
                    ->whereNotNull('rendered_at')->first();
            } catch (Throwable $e) {
                self::$memo[$key] = null; // table missing before migrate: behave like "no render"
            }
        }

        return self::$memo[$key];
    }

    /** $variant: full.png | full.jpg | thumb.jpg | og.jpg | anim.gif | anim.mp4 */
    public static function url(string $type, $flowId, string $variant = 'full.png', bool $legacy = true): ?string
    {
        if ($url = self::record($type, $flowId)?->url($variant)) {
            return $url;
        }

        return $legacy ? self::legacyUrl($type, $flowId, $variant) : null;
    }

    public static function legacyUrl(string $type, $flowId, string $variant = 'full.png'): ?string
    {
        if (! config('render.legacy_fallback') || ! is_numeric($flowId) || $type !== 'sportvatar') {
            return null;
        }

        $base = rtrim((string) config('sportvatar.imagesUrl'), '/');
        if ($variant === 'anim.gif' || $variant === 'anim.mp4') {
            // legacy exports of the old pipeline
            $ext = substr($variant, 5);

            return $base.'/'.$ext.'/'.(int) $flowId.'.'.$ext;
        }

        return $base.'/png/'.(int) $flowId.'.png';
    }

    /**
     * Download links for an item page: PNG/JPG always (route 302s to the best render: browser > server > legacy),
     * GIF/MP4 only when the browser render has them, or (prod) a legacy export exists for this type.
     * Item-level data only, so cached anonymous HTML stays user-agnostic.
     *
     * @return array<string, string> ext => url (png, jpg[, gif, mp4])
     */
    public static function downloads(string $type, $flowId): array
    {
        $r = '/api/image/r/'.$type.'/'.(int) $flowId.'/';
        $out = ['png' => $r.'full.png', 'jpg' => $r.'full.jpg'];
        $record = self::record($type, $flowId);
        $legacyAnim = config('render.legacy_fallback') && in_array($type, ['sportvatar'], true) && ! ($record && $record->source === 'browser');
        foreach (['gif', 'mp4'] as $ext) {
            if (($record && $record->url('anim.'.$ext)) || $legacyAnim) {
                $out[$ext] = $r.'anim.'.$ext;
            }
        }

        return $out;
    }

    /** og:image for an item page; falls back to the site share card. */
    public static function og(string $type, $flowId): array
    {
        if ($url = self::record($type, $flowId)?->url('og.jpg')) {
            return ['url' => $url, 'width' => 1200, 'height' => 630, 'type' => 'image/jpeg'];
        }
        if ($url = self::legacyUrl($type, $flowId)) {
            return ['url' => $url, 'width' => 480, 'height' => 480, 'type' => 'image/png'];
        }

        return ['url' => rtrim((string) config('app.url'), '/').'/images/share.png', 'width' => 1200, 'height' => 630, 'type' => 'image/png'];
    }
}
