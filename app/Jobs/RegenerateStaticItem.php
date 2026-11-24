<?php

namespace App\Jobs;

use App\Services\Headless\StaticSiteBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Re-render one item page of the static headless site (owner/listing/sale/metadata changed) and upload it.
 * Debounced by App\Support\HeadlessRegen::touch(): at most one pending job per item; the marker is cleared
 * when the job starts, so a change that arrives during the render schedules another run. Idempotent:
 * an unchanged page (same md5 as the R2 ETag) is not uploaded.
 */
class RegenerateStaticItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(public string $type, public int $flowId)
    {
    }

    public function handle(): void
    {
        Cache::forget(\App\Support\HeadlessRegen::key($this->type, $this->flowId));
        $result = (new StaticSiteBuilder())->buildItem($this->type, $this->flowId);
        Log::info('headless: regenerated '.$this->type.' #'.$this->flowId.': '.$result);
    }

    public function backoff(): array
    {
        return [30, 120];
    }
}
