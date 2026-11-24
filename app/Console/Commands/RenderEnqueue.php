<?php

namespace App\Console\Commands;

use App\Models\RenderedImage;
use App\Models\RenderJob;
use App\Services\Render\AnimatedItems;
use App\Services\Render\ItemRenderer;
use App\Services\Render\RenderJobs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Queue browser (Chromium) render jobs for the agent-box worker. Default: every item of the type(s) that has
 * no browser render yet; animated items get priority 30, the rest 10 (events use 80-90, "no image" 100).
 *
 *   php8.5 artisan render:enqueue all --dry-run                 # counts only
 *   php8.5 artisan render:enqueue all                           # full backfill (low priority)
 *   php8.5 artisan render:enqueue flovatar --ids=6720 --priority=100
 *   php8.5 artisan render:enqueue --status                      # queue counts
 */
class RenderEnqueue extends Command
{
    protected $signature = 'render:enqueue
        {type=all : '.'item type or all}
        {--ids= : comma list / ranges (e.g. 1,5,10-20)}
        {--priority= : fixed priority (default: animated 30, static 10)}
        {--include-browser : also items that already have a browser render (re-render)}
        {--retry-failed : re-open failed jobs too}
        {--dry-run : only count}
        {--status : print queue counts and exit}';

    protected $description = 'Queue browser render jobs (PNG/JPG/GIF/MP4 from the agent-box Chromium worker)';

    public function handle(): int
    {
        if ($this->option('status')) {
            $this->line(json_encode(['counts' => RenderJobs::counts(),
                'pending_by_priority' => RenderJob::where('status', 'pending')->selectRaw('priority, count(*) c')->groupBy('priority')->orderByDesc('priority')->pluck('c', 'priority'),
                'failed' => RenderJob::where('status', 'failed')->limit(20)->get(['item_type', 'flow_id', 'last_error'])], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }
        $types = $this->argument('type') === 'all' ? ItemRenderer::TYPES : [$this->argument('type')];
        $fixed = $this->option('priority') !== null ? (int) $this->option('priority') : null;
        $total = 0;
        foreach ($types as $type) {
            if (in_array($type, (array) config('render.jobs.skip_types', []), true)) {
                $this->info($type.': skipped (render.jobs.skip_types - served as SVG, share JPG from the server render)');

                continue;
            }
            if (! in_array($type, ItemRenderer::TYPES, true)) {
                $this->error('unknown type '.$type);

                return self::FAILURE;
            }
            $ids = ItemRenderer::query($type)->whereNotNull('flow_id')->orderBy('flow_id')->pluck('flow_id')->map(fn ($v) => (int) $v)->all();
            if ($this->option('ids')) {
                $ids = array_values(array_intersect($ids, $this->parseIds((string) $this->option('ids'))));
            }
            if (! $this->option('include-browser')) {
                $browser = RenderedImage::where('item_type', $type)->where('source', 'browser')->whereNotNull('rendered_at')->pluck('flow_id')->map(fn ($v) => (int) $v)->all();
                $ids = array_values(array_diff($ids, $browser));
            }
            $animated = $fixed === null && $ids ? array_flip(AnimatedItems::ids($type, count($ids) < 2000 ? $ids : null)) : [];
            $nAnim = count(array_intersect_key(array_flip($ids), $animated));
            $this->info(sprintf('%s: %d item(s) to queue (%d animated)', $type, count($ids), $nAnim));
            $total += count($ids);
            if ($this->option('dry-run') || ! $ids) {
                continue;
            }
            $now = now();
            foreach (array_chunk($ids, 500) as $chunk) {
                $rows = array_map(fn ($id) => ['item_type' => $type, 'flow_id' => $id, 'status' => 'pending',
                    'priority' => $fixed ?? (isset($animated[$id]) ? RenderJobs::P_ANIMATED : RenderJobs::P_REST),
                    'reason' => $fixed !== null ? 'manual' : 'backfill', 'created_at' => $now, 'updated_at' => $now], $chunk);
                DB::table('render_jobs')->insertOrIgnore($rows);
                // existing rows: re-open finished ones, raise priority of pending ones (grouped by priority)
                $byPriority = [];
                foreach ($rows as $r) {
                    $byPriority[$r['priority']][] = $r['flow_id'];
                }
                $reopen = $this->option('retry-failed') ? ['done', 'failed'] : ['done'];
                foreach ($byPriority as $prio => $pids) {
                    $q = RenderJob::where('item_type', $type)->whereIn('flow_id', $pids);
                    (clone $q)->whereIn('status', $reopen)->update(['status' => 'pending', 'priority' => $prio, 'attempts' => 0,
                        'available_at' => null, 'reason' => $fixed !== null ? 'manual' : 'backfill', 'updated_at' => $now]);
                    (clone $q)->where('status', 'pending')->where('priority', '<', $prio)->update(['priority' => $prio, 'available_at' => null, 'updated_at' => $now]);
                }
            }
        }
        $this->info(($this->option('dry-run') ? 'would queue ' : 'queued ').$total.' item(s); '.json_encode(RenderJobs::counts()));

        return self::SUCCESS;
    }

    /** @return int[] */
    private function parseIds(string $spec): array
    {
        $ids = [];
        foreach (explode(',', $spec) as $part) {
            if (preg_match('/^\s*(\d+)\s*-\s*(\d+)\s*$/', $part, $m)) {
                $ids = array_merge($ids, range((int) $m[1], (int) $m[2]));
            } elseif (is_numeric(trim($part))) {
                $ids[] = (int) trim($part);
            }
        }

        return array_values(array_unique($ids));
    }
}
