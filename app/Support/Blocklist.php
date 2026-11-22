<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses and NFTs linked to the 2026-11-16 incident (config sportvatar.blockedAddresses
 * + table flagged_items). Used to hide them from marketplace views and to show a notice.
 */
class Blocklist
{
    public static function addresses(): array
    {
        return config('sportvatar.blockedAddresses', []);
    }

    public static function isBlocked(?string $address): bool
    {
        return $address !== null && in_array(strtolower($address), self::addresses(), true);
    }

    /** Morph class => list of flagged flow ids. */
    public static function flaggedIds(string $itemType): array
    {
        return Cache::remember('flagged-items-'.md5($itemType), now()->addMinutes(5), function () use ($itemType) {
            if (! Schema::hasTable('flagged_items')) {
                return [];
            }

            return DB::table('flagged_items')->where('item_type', $itemType)->pluck('flow_id')->map(fn ($id) => (int) $id)->all();
        });
    }

    public static function isFlagged(string $itemType, $flowId): bool
    {
        return $flowId !== null && in_array((int) $flowId, self::flaggedIds($itemType), true);
    }

    public static function notice(): string
    {
        return (string) config('sportvatar.blockedNotice');
    }
}
