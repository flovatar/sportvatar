<?php

namespace App\Services\Headless;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleOffer;
use App\Models\Sportbit;
use App\Models\Sportvatar;
use App\Models\SportvatarTemplate;
use App\Support\Blocklist;
use App\Support\Headless;
use App\Support\ItemImages;
use Illuminate\Database\Eloquent\Model;

/**
 * Item snapshots: the DB view of one NFT (owner, listing, sales, images) used by
 *  - GET /api/v2/{sportvatars|sportbits}/{id} (cached 60 s at the edge), and
 *  - the per-item static pages (embedded as window.HEADLESS.item.snapshot at build time).
 * The browser treats the snapshot as a hint and confirms owner + listing on chain (FCL) - chain-first.
 * Never contains SVG blobs.
 */
class ItemSnapshot
{
    public const TYPES = [
        'sportvatar' => Sportvatar::class,
        'sportbit' => Sportbit::class,
    ];

    /** URL path segment per type (canonical item URL = /{segment}/{id}; Sportbit pages keep /components/{id}/{owner}). */
    public const SEGMENTS = [
        'sportvatar' => 'sportvatars',
        'sportbit' => 'components',
    ];

    /** API v2 segment per type (/api/v2/{segment}/{id}). */
    public const API_SEGMENTS = [
        'sportvatars' => 'sportvatar',
        'sportbits' => 'sportbit',
        'components' => 'sportbit',
    ];

    public static function find(string $type, int $flowId): ?Model
    {
        $class = self::TYPES[$type] ?? null;
        if (! $class) {
            return null;
        }
        if ($type === 'sportvatar') {
            $item = Sportvatar::query()
                ->select(['id', 'mint', 'name', 'sportvatar_series_id', 'flow_id', 'rarity', 'ability', 'flow_address', 'created_at', 'updated_at'])
                ->selectRaw("JSON_REMOVE(flow_metadata, '$.svg') as flow_metadata")
                ->where('flow_id', $flowId)->first();
            $item?->setAppends([]);

            return $item;
        }

        $item = Sportbit::query()
            ->select(['id', 'sportvatar_template_id', 'flow_id', 'flow_address', 'flow_metadata', 'mint', 'destroyed', 'packed', 'created_at', 'updated_at'])
            ->where('flow_id', $flowId)->first();
        $item?->setAppends([]);

        return $item;
    }

    public static function build(string $type, int $flowId): ?array
    {
        $item = self::find($type, $flowId);
        if (! $item) {
            return null;
        }
        $meta = self::meta($item->getAttribute('flow_metadata'));
        $owner = $item->flow_address ? strtolower($item->flow_address) : null;
        $tpl = null;
        if ($type === 'sportbit' && $item->sportvatar_template_id) {
            $tpl = SportvatarTemplate::query()->select(Controller::TEMPLATE_LEAN_COLUMNS)->find($item->sportvatar_template_id);
        }
        $imgType = $type === 'sportvatar' ? 'sportvatar' : 'template';
        $imgId = $type === 'sportvatar' ? $flowId : (int) ($tpl?->flow_id ?? 0);

        $out = [
            'type' => $type,
            'id' => $flowId,
            'name' => ($type === 'sportvatar' ? ($item->name ?: ($meta['name'] ?? null)) : $tpl?->name) ?: null,
            'owner' => $owner,
            'url' => $type === 'sportvatar'
                ? Headless::frontendUrl('sportvatars/'.$flowId)
                : Headless::frontendUrl('components/'.$flowId.($owner ? '/'.$owner : '')),
            'flagged' => Blocklist::isFlagged(self::TYPES[$type], $flowId),
            'owner_blocked' => Blocklist::isBlocked($owner),
            'images' => self::images($imgType, $imgId),
            'listing' => self::listing($item),
            'sales' => self::sales($item),
            'updated_at' => optional($item->updated_at)->toIso8601String(),
        ];

        if ($type === 'sportvatar') {
            $out['mint'] = (int) ($item->mint ?? ($meta['mint'] ?? 0));
            $out['rarity'] = $item->rarity ?: ($meta['rarity'] ?? null);
            $out['ability'] = $item->ability !== null ? (int) $item->ability : null;
            $out['series'] = isset($meta['series']) ? (int) $meta['series'] : null;
            $out['creator'] = $meta['creatorAddress'] ?? null;
            $out['stats'] = $meta['stats'] ?? null;
            $out['combination'] = $meta['combination'] ?? null;
            $out['layers'] = collect($meta['layers'] ?? [])->map(fn ($l) => is_array($l) ? (int) ($l['value'] ?? 0) : (int) $l)->all();
        } else {
            $out['template'] = $tpl ? self::template($tpl) : null;
            $out['mint'] = (int) $item->mint;
            $out['destroyed'] = (bool) $item->destroyed;
            $out['packed'] = (bool) $item->packed;
        }

        return $out;
    }

    public static function template(SportvatarTemplate $tpl): array
    {
        return [
            'id' => (int) $tpl->flow_id,
            'db_id' => (int) $tpl->id,
            'name' => $tpl->name,
            'description' => $tpl->description,
            'layer' => (int) $tpl->layer,
            'rarity' => $tpl->rarity,
            'sport' => $tpl->sport,
            'max_mintable' => (int) $tpl->max_mintable,
            'minted' => (int) $tpl->minted,
            'series_db_id' => $tpl->sportvatar_series_id !== null ? (int) $tpl->sportvatar_series_id : null,
            'images' => self::images('template', (int) $tpl->flow_id),
        ];
    }

    public static function images(string $imgType, int $id): array
    {
        $og = ItemImages::og($imgType, $id);
        $base = '/api/image/r/'.$imgType.'/'.$id.'/';

        return [
            'og' => $og['url'],
            'full_png' => ItemImages::url($imgType, $id, 'full.png') ?? $base.'full.png',
            'thumb' => ItemImages::url($imgType, $id, 'thumb.jpg') ?? $base.'thumb.jpg',
            'svg' => $imgType === 'sportvatar' ? '/api/image/'.$id : '/api/image/template/'.$id,
            'downloads' => ItemImages::downloads($imgType, $id),
        ];
    }

    private static function listing(Model $item): ?array
    {
        $offer = SaleOffer::query()->notBlocked()
            ->where('item_type', get_class($item))->where('item_id', $item->id)
            ->where('is_completed', false)->whereNotNull('listing_id')->orderByDesc('created_at')
            ->first(['id', 'flow_id', 'flow_address', 'price', 'currency', 'is_dapper', 'listing_id', 'created_at']);

        return $offer ? [
            'price' => (float) $offer->price,
            'currency' => $offer->currency ?: ($offer->is_dapper ? 'DUC' : 'FLOW'),
            'is_dapper' => (bool) $offer->is_dapper,
            'seller' => $offer->flow_address,
            'listing_id' => $offer->listing_id !== null ? (string) $offer->listing_id : null,
            'listed_at' => optional($offer->created_at)->toIso8601String(),
        ] : null;
    }

    private static function sales(Model $item): array
    {
        return Sale::query()->notBlocked()
            ->where('item_type', get_class($item))->where('item_id', $item->id)
            ->orderBy('created_at')->limit(100)
            ->get(['price', 'currency', 'is_dapper', 'flow_buyer_address', 'flow_seller_address', 'transaction_id', 'completed_at', 'created_at'])
            ->map(fn ($s) => [
                'price' => (float) $s->price,
                'currency' => $s->currency ?: ($s->is_dapper ? 'DUC' : 'FLOW'),
                'buyer' => $s->flow_buyer_address,
                'seller' => $s->flow_seller_address,
                'tx' => $s->transaction_id,
                'at' => optional($s->completed_at ?? $s->created_at)->toIso8601String(),
            ])->all();
    }

    private static function meta($value): array
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            return json_decode($value, true) ?: [];
        }

        return [];
    }
}
