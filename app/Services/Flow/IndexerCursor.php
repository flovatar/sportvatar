<?php

namespace App\Services\Flow;

use App\Models\Sync;

/**
 * Block-height cursors of the event indexer, stored in the existing `syncs` table.
 * The cursor is the last block height whose events are fully stored.
 */
class IndexerCursor
{
    const REST = 'indexer:rest';
    const STREAM = 'indexer:stream';

    public static function get(string $name): ?int
    {
        $row = Sync::where('name', $name)->first();

        return $row ? (int) $row->height : null;
    }

    public static function set(string $name, int $height): void
    {
        Sync::updateOrCreate(['name' => $name], ['height' => $height]);
    }

    /** Starting point when a cursor does not exist yet. */
    public static function initial(): int
    {
        $configured = config('services.flow.indexer.start_height');
        if ($configured) {
            return (int) $configured - 1;
        }
        $other = max(self::get(self::REST) ?? 0, self::get(self::STREAM) ?? 0);
        if ($other > 0) {
            return $other;
        }

        return (int) \DB::table('events')->max('block_id');
    }
}
