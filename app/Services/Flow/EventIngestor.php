<?php

namespace App\Services\Flow;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stores raw Flow events in the `events` table with the same shape the Graffle webhook used,
 * so ProcessGraffleEvents can process them unchanged. Duplicate deliveries (REST + stream +
 * backfill) are ignored through the unique key (transaction_id, event_index).
 */
class EventIngestor
{
    /**
     * @param  array  $rows  block_height, block_timestamp, type, transaction_id, transaction_index, event_index,
     *                       payload (base64 JSON-Cadence) or flow_metadata (already decoded)
     * @return array [inserted, dropped]
     */
    public function store(array $rows, string $source): array
    {
        $now = now();
        $insert = [];
        $dropped = 0;

        foreach ($rows as $r) {
            $payload = array_key_exists('flow_metadata', $r) ? $r['flow_metadata'] : CadenceJson::decodePayload($r['payload']);
            if (! FlowEventTypes::keep($r['type'], $payload)) {
                $dropped++;

                continue;
            }
            $txIndex = (int) $r['transaction_index'];
            $evIndex = (int) $r['event_index'];
            $insert[] = [
                'block_id' => (int) $r['block_height'],
                'transaction_id' => $r['transaction_id'],
                'type' => $r['type'],
                // transaction_index * 100000 + event_index: unique per block and sorts in execution order
                'index' => $txIndex * 100000 + $evIndex,
                'transaction_index' => $txIndex,
                'event_index' => $evIndex,
                'flow_metadata' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'synced' => false,
                'graffle' => false,
                'source' => $source,
                'event_date' => Carbon::parse($r['block_timestamp'])->utc()->format('Y-m-d H:i:s.u'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = 0;
        foreach (array_chunk($insert, 500) as $chunk) {
            $inserted += DB::table('events')->insertOrIgnore($chunk);
        }

        return [$inserted, $dropped];
    }
}
