<?php

namespace App\Console\Commands;

use App\Services\Flow\EventIngestor;
use App\Services\Flow\IndexerCursor;
use Illuminate\Console\Command;
use PDO;

/**
 * Imports events from the WebSocket backfill SQLite produced by the Python prototype
 * (flovatar-kb/indexer, table `events` with Laravel columns + transaction_index).
 * Rows keep source=backfill and are processed by flow:process like live events.
 */
class FlowImportBackfill extends Command
{
    protected $signature = 'flow:import-backfill {path : SQLite file} {--end-height= : last fully scanned height; sets the REST/stream cursors if they are behind}';

    protected $description = 'Import backfilled Flow events from the indexer prototype SQLite file';

    public function handle(EventIngestor $ingestor): int
    {
        $pdo = new PDO('sqlite:'.$this->argument('path'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query('select block_id, transaction_id, type, "index", transaction_index, flow_metadata, event_date from events order by block_id, "index"');

        $batch = [];
        $inserted = $dropped = $read = 0;
        $flush = function () use (&$batch, &$inserted, &$dropped, $ingestor) {
            [$i, $d] = $ingestor->store($batch, 'backfill');
            $inserted += $i;
            $dropped += $d;
            $batch = [];
        };

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $read++;
            $txIndex = (int) $r['transaction_index'];
            $batch[] = [
                'block_height' => (int) $r['block_id'],
                'block_timestamp' => $r['event_date'].'Z',
                'type' => $r['type'],
                'transaction_id' => $r['transaction_id'],
                'transaction_index' => $txIndex,
                'event_index' => (int) $r['index'] - $txIndex * 100000,
                'flow_metadata' => json_decode($r['flow_metadata'], true),
            ];
            if (count($batch) >= 1000) {
                $flush();
            }
        }
        if ($batch) {
            $flush();
        }

        $this->info("read $read, inserted $inserted, skipped (other projects / not our NFTs) $dropped");

        if ($end = $this->option('end-height')) {
            foreach ([IndexerCursor::REST, IndexerCursor::STREAM] as $name) {
                if ((IndexerCursor::get($name) ?? 0) < (int) $end) {
                    IndexerCursor::set($name, (int) $end);
                    $this->info("cursor $name = $end");
                }
            }
        }

        return self::SUCCESS;
    }
}
