<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingFlowEvents;
use App\Services\Flow\FlowRestClient;
use App\Services\Flow\IndexerCursor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlowStatus extends Command
{
    protected $signature = 'flow:status {--json}';

    protected $description = 'Show Flow indexer cursors, lag and pending events';

    public function handle(FlowRestClient $flow): int
    {
        $sealed = $flow->sealedHeight();
        $rest = IndexerCursor::get(IndexerCursor::REST);
        $stream = IndexerCursor::get(IndexerCursor::STREAM);
        $data = [
            'sealed_height' => $sealed,
            'rest_cursor' => $rest,
            'rest_lag_blocks' => $rest ? $sealed - $rest : null,
            'stream_cursor' => $stream,
            'stream_lag_blocks' => $stream ? $sealed - $stream : null,
            'pending_events' => ProcessPendingFlowEvents::pendingQuery()->count(),
            'gave_up_events' => DB::table('events')->where('synced', false)->whereIn('source', ['rest', 'stream', 'backfill'])
                ->where('attempts', '>=', (int) config('services.flow.indexer.max_attempts', 3))->count(),
            'last_event' => DB::table('events')->whereNotNull('source')->orderByDesc('block_id')->value('event_date'),
            'events_by_source' => DB::table('events')->selectRaw('coalesce(source, if(graffle, "graffle", "legacy")) s, count(*) n')->groupBy('s')->pluck('n', 's'),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
        } else {
            foreach ($data as $k => $v) {
                $this->line(str_pad($k, 18).' '.(is_scalar($v) || $v === null ? var_export($v, true) : json_encode($v)));
            }
        }

        return self::SUCCESS;
    }
}
