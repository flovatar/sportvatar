<?php

namespace App\Jobs;

use App\Services\Render\ItemRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-render one item's static images (PNG/JPG/og) on R2 after a mint/update. Unique per item until it
 * starts, so a burst of events for the same item renders once; unchanged items cost one hash compare.
 */
class RenderItemImages implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180;
    public $backoff = [30, 300];
    public $uniqueFor = 600;

    public function __construct(public string $type, public int $flowId, public bool $force = false)
    {
        $this->onQueue('images');
    }

    public function uniqueId(): string
    {
        return $this->type.':'.$this->flowId;
    }

    public function handle(ItemRenderer $renderer): void
    {
        if (! config('render.enabled')) {
            return;
        }
        $res = $renderer->render($this->type, $this->flowId, $this->force);
        if ($res['status'] === 'error') {
            info('RenderItemImages '.$this->uniqueId().' failed: '.$res['error']);
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);

            return;
        }
        if ($res['status'] === 'rendered') {
            info('RenderItemImages '.$this->uniqueId().' rendered '.$res['hash'].' ('.$res['ms'].' ms, '.$res['bytes'].' B)');
        }
    }

    /** Debounced dispatch (events for one item tend to arrive in bursts). */
    public static function queue(string $type, $flowId, int $delaySeconds = 15): void
    {
        if (! config('render.enabled') || ! is_numeric($flowId)) {
            return;
        }
        static::dispatch($type, (int) $flowId)->delay(now()->addSeconds($delaySeconds));
    }
}
