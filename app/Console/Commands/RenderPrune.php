<?php

namespace App\Console\Commands;

use App\Models\RenderedImage;
use App\Services\Render\ItemRenderer;
use App\Services\Render\RenderPrune as Selector;
use Aws\S3\Exception\S3Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Weekly R2 clean-up of superseded render versions (keys {prefix}{type}/r/{id}/{hash}[-b]/{variant}).
 * Keeps every item's CURRENT version (rendered_images.hash = latest.json hash, server + -b browser folders) and deletes
 * older version folders only when they were superseded more than --days ago. Selection rules (conservative, "if unsure,
 * keep"): App\Services\Render\RenderPrune. Lists ONLY {render.prefix}{type}/r/ on the render disk: never the other
 * network's prefix, legacy exports ({type}/png|gif|mp4/...), latest.json or the headless site buckets.
 *
 *   php8.5 artisan render:prune --dry-run              # counts + bytes, deletes nothing
 *   php8.5 artisan render:prune                        # delete versions superseded > 30 days ago
 *   php8.5 artisan render:prune --type=sportvatar --days=60 --limit=100 -v
 */
class RenderPrune extends Command
{
    protected $signature = 'render:prune
        {--days=30 : delete versions superseded more than this many days ago}
        {--dry-run : only print what would be deleted (counts/bytes)}
        {--limit=0 : max versions to delete in this run (0 = no limit)}
        {--type=* : item type(s) (default: all render types)}';

    protected $description = 'Delete superseded render versions from R2 (keeps current versions; weekly via bin/heavy-job)';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $types = $this->option('type') ?: ItemRenderer::TYPES;
        foreach ($types as $t) {
            if (! in_array($t, ItemRenderer::TYPES, true)) {
                $this->error('unknown type '.$t.' (types: '.implode(', ', ItemRenderer::TYPES).')');

                return self::FAILURE;
            }
        }
        $disk = Storage::disk(config('render.disk'));
        if (! method_exists($disk, 'getClient')) {
            $this->error('render disk '.config('render.disk').' is not an S3/R2 disk');

            return self::FAILURE;
        }
        $client = $disk->getClient();
        $bucket = (string) config('filesystems.disks.'.config('render.disk').'.bucket');
        $cutoff = now()->subDays($days)->getTimestamp();

        $sum = ['dry_run' => $dry, 'days' => $days, 'bucket' => $bucket, 'objects' => 0, 'bytes' => 0, 'items' => 0,
            'items_multi' => 0, 'versions_old' => 0, 'eligible_versions' => 0, 'eligible_objects' => 0, 'eligible_bytes' => 0,
            'kept_recent_versions' => 0, 'kept_recent_bytes' => 0, 'kept_unsure_versions' => 0, 'kept_items_unsure' => 0,
            'deleted_versions' => 0, 'deleted_objects' => 0, 'deleted_bytes' => 0, 'errors' => 0, 'by_type' => []];
        $budget = $limit ?: PHP_INT_MAX;

        foreach ($types as $type) {
            // '{prefix}{type}/r/' - basePath() also enforces the testnet/ prefix off mainnet
            $typePrefix = substr(ItemRenderer::basePath($type, 0), 0, -1);
            if (! str_ends_with($typePrefix, $type.'/r/')) {
                $this->error('unexpected key prefix '.$typePrefix);

                return self::FAILURE;
            }
            $items = [];
            $token = null;
            do {
                $params = ['Bucket' => $bucket, 'Prefix' => $typePrefix, 'MaxKeys' => 1000];
                if ($token) {
                    $params['ContinuationToken'] = $token;
                }
                $res = $client->listObjectsV2($params);
                foreach ($res['Contents'] ?? [] as $o) {
                    $rel = substr($o['Key'], strlen($typePrefix));
                    $sum['objects']++;
                    $sum['bytes'] += (int) $o['Size'];
                    if (! preg_match('/^(\d+)\/(.+)$/', $rel, $m)) {
                        continue;
                    }
                    $items[(int) $m[1]][] = ['key' => $m[2], 'size' => (int) $o['Size'],
                        'modified' => $o['LastModified'] instanceof \DateTimeInterface ? $o['LastModified']->getTimestamp() : strtotime((string) $o['LastModified'])];
                }
                $token = ! empty($res['IsTruncated']) ? ($res['NextContinuationToken'] ?? null) : null;
            } while ($token);

            $t = ['items' => count($items), 'items_multi' => 0, 'eligible_versions' => 0, 'eligible_bytes' => 0, 'deleted_versions' => 0, 'deleted_bytes' => 0];
            $sum['items'] += count($items);
            // only items with more than one version folder can have something to prune
            $multi = array_filter($items, fn ($objs) => count(array_unique(array_filter(array_map(
                fn ($o) => preg_match(Selector::HASH_RE, $o['key'], $m) ? $m[1] : null, $objs)))) > 1);
            $t['items_multi'] = count($multi);
            $sum['items_multi'] += count($multi);
            $rows = $multi ? RenderedImage::where('item_type', $type)->whereIn('flow_id', array_keys($multi))->pluck('hash', 'flow_id')->all() : [];

            foreach ($multi as $id => $objs) {
                $latest = $this->latestHash($client, $bucket, $typePrefix.$id.'/latest.json');
                $sel = Selector::select($objs, $rows[$id] ?? null, $latest, $cutoff);
                if ($sel['keep_all'] !== null) {
                    $sum['kept_items_unsure']++;
                    $this->line("  keep all {$type} #{$id}: {$sel['keep_all']}", null, 'v');

                    continue;
                }
                $sum['versions_old'] += count($sel['delete']) + count($sel['recent']) + count($sel['unsure']);
                $sum['kept_recent_versions'] += count($sel['recent']);
                $sum['kept_recent_bytes'] += array_sum($sel['recent']);
                $sum['kept_unsure_versions'] += count($sel['unsure']);
                foreach ($sel['unsure'] as $h => $why) {
                    $this->line("  keep {$type} #{$id} {$h}: {$why}", null, 'v');
                }
                foreach ($sel['recent'] as $h => $b) {
                    $this->line("  keep {$type} #{$id} {$h}: superseded < {$days} days ago", null, 'v');
                }
                foreach ($sel['delete'] as $h => $v) {
                    $sum['eligible_versions']++;
                    $sum['eligible_objects'] += count($v['keys']);
                    $sum['eligible_bytes'] += $v['bytes'];
                    $t['eligible_versions']++;
                    $t['eligible_bytes'] += $v['bytes'];
                    $when = date('Y-m-d', $v['superseded_at']);
                    if ($dry || $budget <= 0) {
                        $this->line("  ".($dry ? 'would delete' : 'over --limit, kept')." {$type} #{$id} {$h} (".count($v['keys'])." objects, {$v['bytes']} B, superseded {$when})", null, 'v');

                        continue;
                    }
                    $keys = [];
                    foreach ($v['keys'] as $k) {
                        // belt and braces: only {typePrefix}{id}/{this old hash}[-b]/{variant}, never the current hash
                        if (! preg_match(Selector::HASH_RE, $k, $m) || $m[1] !== $h || in_array($m[1], $sel['current'], true)) {
                            continue 2;
                        }
                        $keys[] = ['Key' => $typePrefix.$id.'/'.$k];
                    }
                    try {
                        foreach (array_chunk($keys, 1000) as $chunk) {
                            $r = $client->deleteObjects(['Bucket' => $bucket, 'Delete' => ['Objects' => $chunk, 'Quiet' => true]]);
                            if (! empty($r['Errors'])) {
                                $sum['errors'] += count($r['Errors']);
                                $this->warn("  {$type} #{$id} {$h}: ".count($r['Errors']).' delete errors');
                            }
                        }
                        $budget--;
                        $sum['deleted_versions']++;
                        $sum['deleted_objects'] += count($keys);
                        $sum['deleted_bytes'] += $v['bytes'];
                        $t['deleted_versions']++;
                        $t['deleted_bytes'] += $v['bytes'];
                        $this->line("  deleted {$type} #{$id} {$h} (".count($keys)." objects, {$v['bytes']} B, superseded {$when})", null, 'v');
                    } catch (\Throwable $e) {
                        $sum['errors']++;
                        $this->warn("  {$type} #{$id} {$h}: ".substr($e->getMessage(), 0, 200));
                    }
                }
            }
            $sum['by_type'][$type] = $t;
            $this->info(sprintf('%s: %d items, %d with >1 version, %d versions superseded > %d days (%.2f MB), %s %d (%.2f MB)',
                $type, $t['items'], $t['items_multi'], $t['eligible_versions'], $days, $t['eligible_bytes'] / 1048576,
                $dry ? 'dry-run, deleted' : 'deleted', $t['deleted_versions'], $t['deleted_bytes'] / 1048576));
        }

        $this->info(sprintf('render:prune%s bucket %s: %d objects / %.2f MB under {type}/r/, %d items (%d with >1 version); old versions %d: eligible %d (%d objects, %.2f MB), kept recent %d, kept unsure %d (+%d items kept whole); deleted %d versions (%d objects, %.2f MB), errors %d',
            $dry ? ' (dry-run)' : '', $bucket, $sum['objects'], $sum['bytes'] / 1048576, $sum['items'], $sum['items_multi'],
            $sum['versions_old'], $sum['eligible_versions'], $sum['eligible_objects'], $sum['eligible_bytes'] / 1048576,
            $sum['kept_recent_versions'], $sum['kept_unsure_versions'], $sum['kept_items_unsure'],
            $sum['deleted_versions'], $sum['deleted_objects'], $sum['deleted_bytes'] / 1048576, $sum['errors']));
        Log::info('render:prune summary', $sum);

        return $sum['errors'] ? self::FAILURE : self::SUCCESS;
    }

    /** Hash in latest.json: string, null (no latest.json) or false (unreadable -> caller keeps everything). */
    private function latestHash($client, string $bucket, string $key): string|null|false
    {
        try {
            $body = (string) $client->getObject(['Bucket' => $bucket, 'Key' => $key])['Body'];
        } catch (S3Exception $e) {
            return in_array($e->getAwsErrorCode(), ['NoSuchKey', 'NotFound'], true) || $e->getStatusCode() === 404 ? null : false;
        } catch (\Throwable $e) {
            return false;
        }
        $hash = Selector::latestHash($body);

        return $hash ?? ($body === '' ? null : false);
    }
}
