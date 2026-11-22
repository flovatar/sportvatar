<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Processes indexer events (source rest/stream/backfill) strictly in chain order
 * (block_id, index) with the existing ProcessGraffleEvents logic, one at a time.
 * Unique + a dedicated single-worker queue (`flow-events`) keep ownership and sale
 * updates sequential. Events that stay unsynced are retried up to max_attempts times.
 */
class ProcessPendingFlowEvents implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 1800;

    public $tries = 1;

    public $uniqueFor = 3600;

    public function __construct(public ?int $limit = null)
    {
        $this->onQueue('flow-events');
    }

    public function handle(): void
    {
        // Work through the backlog in batches; stop well before the job timeout. Anything left
        // is picked up by the next run (scheduler: flow:process --queue every minute).
        $deadline = time() + 1200;
        do {
            $result = static::processBatch($this->limit ?? (int) config('services.flow.indexer.process_batch', 500));
        } while ($result['remaining'] > 0 && $result['processed'] > 0 && time() < $deadline);
    }

    public static function pendingQuery()
    {
        return Event::query()
            ->where('synced', false)
            ->where('graffle', false)
            ->whereIn('source', ['rest', 'stream', 'backfill'])
            ->where('attempts', '<', (int) config('services.flow.indexer.max_attempts', 3));
    }

    /**
     * @return array{processed:int, synced:int, failed:int, remaining:int}
     */
    public static function processBatch(int $limit, ?callable $progress = null): array
    {
        $processed = $synced = $failed = 0;
        $events = static::pendingQuery()->orderBy('block_id')->orderBy('index')->limit($limit)->get();

        foreach ($events as $event) {
            $processed++;
            try {
                (new ProcessGraffleEvents($event, false))->handle();
            } catch (\Throwable $e) {
                Log::warning('Flow event processing failed', ['event' => $event->id, 'type' => $event->type, 'error' => substr($e->getMessage(), 0, 500)]);
            }
            $fresh = Event::find($event->id);
            if (! $fresh || $fresh->synced) {
                $synced++;
            } else {
                $failed++;
                $fresh->increment('attempts');
            }
            if ($progress) {
                $progress($processed, $event);
            }
        }

        return ['processed' => $processed, 'synced' => $synced, 'failed' => $failed, 'remaining' => static::pendingQuery()->count()];
    }
}
