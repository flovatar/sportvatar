<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingFlowEvents;
use App\Services\Flow\EventIngestor;
use App\Services\Flow\FlowEventTypes;
use App\Services\Flow\FlowRestClient;
use App\Services\Flow\IndexerCursor;
use Illuminate\Console\Command;

/**
 * REST gap filler for the Flow event indexer (replaces the Graffle webhook).
 * Scans sealed blocks in 250-block windows for every indexed event type and only advances
 * its cursor after a whole window is stored, so it never skips a range. Scheduled every
 * 5 minutes; a cheap safety net next to the flow:stream daemon.
 */
class FlowCatchup extends Command
{
    protected $signature = 'flow:catchup
        {--from= : first block height (overrides the cursor)}
        {--to= : last block height (default: sealed head)}
        {--max-blocks= : stop after this many blocks (default config services.flow.indexer.max_blocks_per_run)}
        {--no-process : do not dispatch processing of new events}';

    protected $description = 'Fetch Sportvatar events from the Flow REST API up to the sealed height';

    public function handle(FlowRestClient $flow, EventIngestor $ingestor): int
    {
        if (! config('services.flow.indexer.enabled')) {
            $this->warn('Indexer disabled (FLOW_INDEXER_ENABLED=false)');

            return self::SUCCESS;
        }

        $cursor = $this->option('from') !== null ? (int) $this->option('from') - 1 : (IndexerCursor::get(IndexerCursor::REST) ?? IndexerCursor::initial());
        $sealed = $flow->sealedHeight();
        $to = min($this->option('to') !== null ? (int) $this->option('to') : $sealed, $sealed);
        $maxBlocks = (int) ($this->option('max-blocks') ?? config('services.flow.indexer.max_blocks_per_run'));
        $to = min($to, $cursor + $maxBlocks);
        $types = FlowEventTypes::all();

        if ($cursor >= $to) {
            $this->info("Up to date (cursor $cursor, sealed $sealed)");

            return self::SUCCESS;
        }

        $this->info("Catching up blocks ".($cursor + 1)." → $to (sealed $sealed, ".count($types).' types)');
        $total = 0;
        $start = $cursor + 1;
        while ($start <= $to) {
            $end = min($start + FlowRestClient::MAX_RANGE - 1, $to);
            $rows = $flow->eventsForTypes($types, $start, $end);
            [$inserted] = $ingestor->store($rows, 'rest');
            $total += $inserted;
            if ($this->option('from') === null) {
                IndexerCursor::set(IndexerCursor::REST, $end);
            }
            if ($inserted > 0) {
                $this->line("  $start-$end: +$inserted events");
            }
            $start = $end + 1;
        }

        $this->info("Done: $total new events, cursor ".($to));
        if ($total > 0 && ! $this->option('no-process')) {
            ProcessPendingFlowEvents::dispatch();
        }

        return self::SUCCESS;
    }
}
