<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingFlowEvents;
use App\Models\Event;
use App\Services\Flow\FlowEventTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Processes pending indexer events in chain order (synchronously, in this process).
 * --collapse marks redundant "re-read from chain" events as synced first: for
 * Updated/NameSet/PositionChanged/StoryAdded only the last event per NFT triggers a sync.
 */
class FlowProcess extends Command
{
    protected $signature = 'flow:process {--limit=500 : events per batch} {--until-empty : keep going until nothing is pending} {--collapse : skip redundant metadata re-syncs (backfill)} {--queue : dispatch to the flow-events queue instead}';

    protected $description = 'Apply pending Flow events (indexer) to the database with ProcessGraffleEvents';

    public function handle(): int
    {
        if ($this->option('queue')) {
            if (! ProcessPendingFlowEvents::pendingQuery()->exists()) {
                return self::SUCCESS;
            }
            ProcessPendingFlowEvents::dispatch((int) $this->option('limit'));
            $this->info('Dispatched to queue flow-events');

            return self::SUCCESS;
        }

        if ($this->option('collapse')) {
            $this->collapse();
        }

        do {
            $t0 = microtime(true);
            $r = ProcessPendingFlowEvents::processBatch((int) $this->option('limit'));
            $this->info(sprintf('processed %d (synced %d, unsynced %d) in %.1fs, remaining %d',
                $r['processed'], $r['synced'], $r['failed'], microtime(true) - $t0, $r['remaining']));
        } while ($this->option('until-empty') && $r['processed'] > 0 && $r['remaining'] > 0);

        return self::SUCCESS;
    }

    protected function collapse(): void
    {
        $groups = FlowEventTypes::resyncGroups();
        $total = 0;
        foreach ($groups as $types) {
            $pending = ProcessPendingFlowEvents::pendingQuery()->whereIn('type', $types)
                ->orderBy('block_id')->orderBy('index')->get(['id', 'flow_metadata']);
            $last = [];
            foreach ($pending as $e) {
                $last[(string) ($e->flow_metadata['id'] ?? '')] = $e->id;
            }
            $redundant = $pending->pluck('id')->diff(array_values($last))->values();
            foreach ($redundant->chunk(1000) as $chunk) {
                $total += Event::whereIn('id', $chunk)->update(['synced' => true]);
            }
        }
        $this->info("Collapsed $total redundant metadata re-sync events");
    }
}
