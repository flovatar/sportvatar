<?php

namespace App\Http\Controllers;

use App\Jobs\RenderItemImages;
use App\Models\Sportvatar;
use App\Models\SportvatarTemplate;
use App\Support\ItemImages;

/**
 * GET /api/image/r/{type}/{flowId}/{variant} -> 302 to the current static render on R2
 * (immutable hashed key). Without a render: the legacy CDN export (prod) or, on testnet / for brand
 * new items, a render is queued and the live SVG is served meanwhile. Never points testnet at prod.
 */
class RenderedImageController extends Controller
{
    public const VARIANTS = ['full.png', 'full.jpg', 'thumb.jpg', 'og.jpg', 'anim.gif', 'anim.mp4'];

    public function __invoke(string $type, $flowId, string $variant = 'full.png')
    {
        return static::respond($type, (int) $flowId, $variant);
    }

    public static function respond(string $type, int $flowId, string $variant)
    {
        if ($url = ItemImages::record($type, $flowId)?->url($variant)) {
            return redirect()->away($url, 302)->header('Cache-Control', 'public, max-age=300');
        }
        if ($legacy = ItemImages::legacyUrl($type, $flowId, $variant)) {
            return redirect()->away($legacy, 302)->header('Cache-Control', 'public, max-age=300');
        }
        if (str_starts_with($variant, 'anim.')) {
            abort(404); // GIF/MP4 only come from the browser renderer (agent box) or the legacy export
        }
        $exists = match ($type) {
            'sportvatar' => Sportvatar::where('flow_id', $flowId)->exists(),
            'template' => SportvatarTemplate::where('flow_id', $flowId)->exists(),
            default => false,
        };
        if (! $exists) {
            abort(404);
        }
        RenderItemImages::debounced($type, $flowId, 0);
        $svg = match ($type) {
            'sportvatar' => '/api/image/'.$flowId,
            'template' => '/api/image/template/'.$flowId,
        };

        return redirect($svg, 302)->header('Cache-Control', 'no-store');
    }
}
