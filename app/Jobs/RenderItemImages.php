<?php

namespace App\Jobs;

use App\Services\Render\ItemRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-render one item's static images (PNG/JPG/og) after a mint/update. Deliberately NOT a unique job
 * (a stale unique lock would silently drop a later update): duplicates from an event burst cost one
 * source compose + hash compare each and end as 'unchanged'.
 */
class RenderItemImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180;
    public $backoff = [30, 300];

    public function __construct(public string $type, public int $flowId, public bool $force = false)
    {
        $this->onQueue('images');
    }

    public function handle(ItemRenderer $renderer): void
    {
        if (! config('render.enabled')) {
            return;
        }
        $res = $renderer->render($this->type, $this->flowId, $this->force);
        if ($res['status'] === 'error') {
            info('RenderItemImages '.$this->type.':'.$this->flowId.' failed: '.$res['error']);
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);

            return;
        }
        if ($res['status'] === 'rendered') {
            info('RenderItemImages '.$this->type.':'.$this->flowId.' rendered '.$res['hash'].' ('.$res['ms'].' ms, '.$res['bytes'].' B)');
        }
    }

    /** Debounced dispatch (events for one item tend to arrive in bursts). Not named queue(): Laravel's dispatcher calls $job->queue() when it exists. */
    public static function debounced(string $type, $flowId, int $delaySeconds = 15): void
    {
        if (! config('render.enabled') || ! is_numeric($flowId)) {
            return;
        }
        static::dispatch($type, (int) $flowId)->delay(now()->addSeconds($delaySeconds));
    }
}
