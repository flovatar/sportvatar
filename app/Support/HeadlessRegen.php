<?php

namespace App\Support;

use App\Jobs\RegenerateStaticItem;
use App\Models\Sale;
use App\Models\SaleOffer;
use App\Models\Sportvatar;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Indexer -> static page regeneration (per-item pages of the headless site). Two triggers:
 *  - model observers: a Sportvatar row (mint/update/transfer/name) or one of its listings/sales changes,
 *    whoever wrote it (indexer jobs, artisan commands);
 *  - fromEvent(): called by the indexer after each applied event (ProcessGraffleEvents), because listing
 *    removals are query-builder deletes without model events.
 * touch() debounces: one delayed RegenerateStaticItem per item per `headless.debounce` seconds.
 * No-op unless HEADLESS_ENABLED=true. Sportbits have no static page (server-rendered, proxied).
 */
class HeadlessRegen
{
    public const MODEL_TYPES = [Sportvatar::class => 'sportvatar'];

    public static function key(string $type, int $flowId): string
    {
        return 'headless-regen:'.$type.':'.$flowId;
    }

    public static function touch(string $type, $flowId): bool
    {
        if (! config('headless.enabled') || ! is_numeric($flowId)) {
            return false;
        }
        $delay = max(1, (int) config('headless.debounce'));
        try {
            if (! Cache::add(self::key($type, (int) $flowId), 1, $delay + 300)) {
                return false; // a job is already pending for this item
            }
            RegenerateStaticItem::dispatch($type, (int) $flowId)->onQueue((string) config('headless.queue'))->delay(now()->addSeconds($delay));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Contract name in an event type -> static item type (A.{addr}.{Contract}.{Event}). */
    public const EVENT_CONTRACTS = ['Sportvatar' => 'sportvatar'];

    /**
     * Indexer hook (after an event was applied): Sportvatar mint/update/deposit/withdraw/name, NFTStorefrontV2
     * listing events for Sportvatar NFTs (nftType + nftID; ResourceDestroyed of a cancelled listing included).
     */
    public static function fromEvent(?string $type, $meta): void
    {
        if (! config('headless.enabled') || ! $type) {
            return;
        }
        $meta = $meta instanceof \Illuminate\Support\Collection ? $meta->toArray() : (array) $meta;
        $parts = explode('.', $type);
        $contract = $parts[2] ?? '';
        $itemType = null;
        $id = null;
        if (isset(self::EVENT_CONTRACTS[$contract])) {
            $itemType = self::EVENT_CONTRACTS[$contract];
            $id = $meta['id'] ?? $meta['tokenId'] ?? $meta['sportvatarId'] ?? null;
        } elseif ($contract === 'NFTStorefrontV2' && isset($meta['nftType'])) {
            $typeId = is_array($meta['nftType']) ? ($meta['nftType']['typeID'] ?? '') : (string) $meta['nftType'];
            foreach (self::EVENT_CONTRACTS as $name => $t) {
                if (str_ends_with($typeId, '.'.$name.'.NFT')) {
                    $itemType = $t;
                    $id = $meta['nftID'] ?? null;
                }
            }
        }
        if ($itemType && $id !== null && is_numeric($id)) {
            self::touch($itemType, $id);
        }
    }

    public static function register(): void
    {
        foreach (self::MODEL_TYPES as $class => $type) {
            $class::saved(function ($item) use ($type) {
                if ($item->wasRecentlyCreated || $item->wasChanged(['flow_address', 'flow_metadata', 'name', 'rarity', 'ability', 'svg'])) {
                    self::touch($type, $item->flow_id);
                }
            });
            $class::deleted(fn ($item) => self::touch($type, $item->flow_id));
        }
        $byListing = function ($row) {
            $type = self::MODEL_TYPES[$row->item_type] ?? null;
            if ($type && $row->item_id) {
                $flowId = $row->item_type::query()->whereKey($row->item_id)->value('flow_id');
                self::touch($type, $flowId);
            }
        };
        SaleOffer::saved($byListing);
        SaleOffer::deleted($byListing);
        Sale::created($byListing);
    }
}
