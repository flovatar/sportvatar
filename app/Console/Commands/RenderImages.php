<?php

namespace App\Console\Commands;

use App\Models\Sportvatar;
use App\Models\SportvatarTemplate;
use App\Models\RenderedImage;
use App\Services\Render\AnimatedItems;
use App\Services\Render\ItemRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill / re-render static item images (resvg -> R2). Resumable: items with a current render are
 * skipped, so re-running continues where it stopped. Throttled: niced, one item at a time, sleeps
 * between items and pauses while the 1-minute load average is high (PHP-FPM keeps priority).
 *
 *   php8.5 artisan render:images sportvatar --missing --dry-run    # count only
 *   php8.5 artisan render:images sportvatar --missing              # items with neither a new nor a legacy PNG
 *   php8.5 artisan render:images sportvatar --ids=4,7,100-120 --force
 */
class RenderImages extends Command
{
    protected $signature = 'render:images
        {type=sportvatar : sportvatar|template|all}
        {--ids= : comma list / ranges (e.g. 1,5,10-20)}
        {--missing : only items without a new render AND without a legacy PNG on the CDN bucket}
        {--all : every item; unchanged ones are skipped by content hash}
        {--legacy-list= : file with flow ids that have a legacy PNG (one per line), instead of listing the bucket}
        {--static-only : skip animated items (CSS @keyframes / SMIL); their images come from the browser renderer}
        {--rendered : only items that already have a render (with --force: re-upload, e.g. after switching RENDER_DISK)}
        {--force : re-render even if the content hash is unchanged}
        {--dry-run : list/count candidates (with --render-local: render without uploading)}
        {--render-local= : with --dry-run, write the variants to this local directory}
        {--limit=0 : stop after N items}
        {--sleep=300 : milliseconds to sleep between items}
        {--max-load=1.6 : pause while the 1-minute load average is above this}';

    protected $description = 'Render item images (PNG/JPG/og) with resvg and upload them to R2 (resumable, throttled)';

    public function handle(ItemRenderer $renderer): int
    {
        if (function_exists('proc_nice')) {
            @proc_nice(10);
        }
        $types = $this->argument('type') === 'all' ? ItemRenderer::TYPES : [$this->argument('type')];
        foreach ($types as $type) {
            if (! in_array($type, ItemRenderer::TYPES, true)) {
                $this->error('unknown type '.$type);

                return self::FAILURE;
            }
            try {
                $ids = $this->candidates($type);
            } catch (\Throwable $e) {
                $this->error($type.': '.$e->getMessage());
                continue;
            }
            if ($this->option('static-only')) {
                $animated = $ids ? AnimatedItems::ids($type, $ids) : [];
                $before = count($ids);
                $ids = array_values(array_diff($ids, $animated));
                $this->line(sprintf('  %s: %d animated item(s) skipped (static-only)', $type, $before - count($ids)));
            }
            $this->info(sprintf('%s: %d candidate(s)%s', $type, count($ids), $ids ? ' (first: '.implode(',', array_slice($ids, 0, 15)).(count($ids) > 15 ? ',…' : '').')' : ''));
            if ($this->option('dry-run') && ! $this->option('render-local')) {
                continue;
            }
            $this->run_($renderer, $type, $ids);
        }

        return self::SUCCESS;
    }

    private function run_(ItemRenderer $renderer, string $type, array $ids): void
    {
        $limit = (int) $this->option('limit');
        $sleep = max(0, (int) $this->option('sleep'));
        $maxLoad = (float) $this->option('max-load');
        $stats = ['rendered' => 0, 'unchanged' => 0, 'missing' => 0, 'error' => 0, 'dry-run' => 0];
        $ms = [];
        $timings = [];
        $t0 = microtime(true);
        $ru0 = getrusage();
        $ruc0 = getrusage(1);
        $n = 0;
        foreach ($ids as $id) {
            if ($limit && $n >= $limit) {
                break;
            }
            while ($maxLoad > 0 && sys_getloadavg()[0] > $maxLoad) {
                $this->line('  load '.sys_getloadavg()[0].' > '.$maxLoad.', waiting…');
                sleep(10);
            }
            $n++;
            $res = $renderer->render($type, (int) $id, (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $stats[$res['status']] = ($stats[$res['status']] ?? 0) + 1;
            if (isset($res['ms'])) {
                $ms[] = $res['ms'];
                foreach ($renderer->timings as $k => $v) {
                    $timings[$k][] = $v;
                }
            }
            if ($res['status'] === 'dry-run' && ($dir = $this->option('render-local'))) {
                @mkdir($dir.'/'.$type.'-'.$id, 0775, true);
                foreach ($res['files'] as $name => [$bytes]) {
                    file_put_contents($dir.'/'.$type.'-'.$id.'/'.$name, $bytes);
                }
            }
            $line = sprintf('  %s #%d %s%s', $type, $id, $res['status'], isset($res['ms']) ? ' '.$res['ms'].' ms '.round(($res['bytes'] ?? 0) / 1024).' KB '.json_encode($renderer->timings) : '');
            $res['status'] === 'error' ? $this->error($line.' '.$res['error']) : $this->line($line);
            if ($sleep && in_array($res['status'], ['rendered', 'dry-run', 'error'], true)) {
                usleep($sleep * 1000);
            }
        }
        $ru = getrusage();
        $ruc = getrusage(1);
        $cpu = fn ($a, $b, $k) => ($b['ru_'.$k.'.tv_sec'] - $a['ru_'.$k.'.tv_sec']) + ($b['ru_'.$k.'.tv_usec'] - $a['ru_'.$k.'.tv_usec']) / 1e6;
        $avg = fn ($l) => $l ? (int) round(array_sum($l) / count($l)) : 0;
        $this->info(sprintf('%s done in %.1f s: %s', $type, microtime(true) - $t0, json_encode($stats)));
        if ($ms) {
            sort($ms);
            $this->info(sprintf('  per item: avg %d ms, p95 %d ms, max %d ms; avg %s; CPU php %.2f s + resvg %.2f s (%.2f s/item); peak PHP mem %.0f MB, largest child process RSS %.0f MB',
                $avg($ms), $ms[(int) floor(0.95 * (count($ms) - 1))], end($ms), json_encode(array_map($avg, $timings)),
                $cpu($ru0, $ru, 'utime') + $cpu($ru0, $ru, 'stime'), $cpu($ruc0, $ruc, 'utime') + $cpu($ruc0, $ruc, 'stime'),
                ($cpu($ru0, $ru, 'utime') + $cpu($ru0, $ru, 'stime') + $cpu($ruc0, $ruc, 'utime') + $cpu($ruc0, $ruc, 'stime')) / count($ms),
                memory_get_peak_usage(true) / 1048576, ($ruc['ru_maxrss'] ?? 0) / 1024));
        }
    }

    /** @return int[] */
    private function candidates(string $type): array
    {
        if ($this->option('ids')) {
            $ids = [];
            foreach (explode(',', (string) $this->option('ids')) as $part) {
                if (preg_match('/^(\d+)-(\d+)$/', trim($part), $m)) {
                    $ids = array_merge($ids, range((int) $m[1], (int) $m[2]));
                } elseif (is_numeric(trim($part))) {
                    $ids[] = (int) trim($part);
                }
            }

            return array_values(array_unique($ids));
        }

        $query = match ($type) {
            'sportvatar' => Sportvatar::query(),
            'template' => SportvatarTemplate::query(),
        };
        $all = $query->whereNotNull('flow_id')->whereNotNull('svg')->orderBy('flow_id')->pluck('flow_id')->map(fn ($v) => (int) $v)->all();
        $done = RenderedImage::where('item_type', $type)->whereNotNull('rendered_at')->whereNull('error')->pluck('flow_id')->map(fn ($v) => (int) $v)->all();
        if ($this->option('rendered')) {
            return array_values(array_intersect($all, $done));
        }
        if ($this->option('all') || $this->option('force')) {
            return $all;
        }
        $ids = array_values(array_diff($all, $done));
        if ($this->option('missing') && $type !== 'template' && config('render.legacy_fallback')) {
            $legacy = $this->legacyIds($type);
            $this->line(sprintf('  %s: %d in DB, %d with a new render, %d with a legacy PNG on the bucket', $type, count($all), count($done), count($legacy)));
            $ids = array_values(array_diff($ids, $legacy));
        }

        return $ids;
    }

    /**
     * flow ids that have a legacy {type}/png/{id}.png object on the CDN bucket. Listed through the 'r2'
     * disk (RENDER_LEGACY_DISK), independent of where new renders go. Throws when the bucket can't be
     * listed: an empty answer would turn --missing into "everything".
     */
    private function legacyIds(string $type): array
    {
        if ($file = $this->option('legacy-list')) {
            return array_values(array_filter(array_map('intval', file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])));
        }
        $ids = [];
        foreach (Storage::disk(config('render.legacy_disk', 'r2'))->files('sportvatar/png') as $path) {
            if (preg_match('#/(\d+)\.png$#', $path, $m)) {
                $ids[] = (int) $m[1];
            }
        }
        if (! $ids) {
            throw new \RuntimeException('no legacy PNGs listed for '.$type.' - refusing to treat every item as missing (use --legacy-list)');
        }

        return $ids;
    }
}
