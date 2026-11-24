<?php

namespace App\Console\Commands;

use App\Services\Headless\StaticSiteBuilder;
use App\Support\Headless;
use Illuminate\Console\Command;

/**
 * Build the static headless site and upload it to R2 (bucket sportvatar-web, prefix Headless::prefix()).
 *
 *   php8.5 artisan headless:build                      # everything: assets, shells, items, sitemap
 *   php8.5 artisan headless:build --shells --sitemap   # hourly
 *   php8.5 artisan headless:build --items --type=sportvatar --ids=1,2,5-9
 *   php8.5 artisan headless:build --out=/tmp/site      # write files locally instead of R2 (preview/tests)
 * Unchanged objects (same md5 as the stored ETag) are skipped, so re-runs are cheap.
 */
class HeadlessBuild extends Command
{
    protected $signature = 'headless:build
        {--assets : public/ assets (css/js/images/fonts)}
        {--shells : shell pages (home, how it works, builder, packs, marketplace, locker room, ..., collection shell, 404)}
        {--items : per-item pages (Sportvatars)}
        {--sitemap : sitemap.xml}
        {--page=* : only these shell page ids}
        {--type= : sportvatar (with --items)}
        {--ids= : comma list / ranges of flow ids (with --items)}
        {--limit= : max items per type}
        {--out= : write to this local directory instead of the disk}
        {--force : upload even when unchanged}
        {--dry-run : render but do not upload}
        {--quiet-puts : do not print every uploaded key}';

    protected $description = 'Render the static headless site (Blade -> HTML) and upload it to R2';

    public function handle(): int
    {
        $all = ! $this->option('assets') && ! $this->option('shells') && ! $this->option('items') && ! $this->option('sitemap');
        $builder = new StaticSiteBuilder($this->option('out') ?: null, (bool) $this->option('force'), (bool) $this->option('dry-run'));
        $quiet = (bool) $this->option('quiet-puts');
        $builder->log = function (string $msg, bool $error) use ($quiet) {
            if ($error) {
                $this->error($msg);
            } elseif (! $quiet) {
                $this->line($msg);
            }
        };
        $target = $this->option('out') ? rtrim($this->option('out'), '/').'/'.Headless::prefix() : config('headless.disk').':'.Headless::prefix();
        $this->info('headless:build -> '.$target.'  (frontend '.config('headless.frontend_url').')');
        $t0 = microtime(true);
        $bulk = $all || $this->option('assets') || ($this->option('items') && ! $this->option('ids'));
        if ($bulk) {
            $builder->loadEtags();
        }
        if ($all || $this->option('assets')) {
            $builder->syncAssets('bundle');
            $builder->syncVersionedBundle();
        }
        if ($all || $this->option('shells')) {
            $builder->buildShells($this->option('page') ?: null);
        }
        if ($all || $this->option('items')) {
            $builder->buildItems($this->option('type') ?: null, $this->parseIds($this->option('ids')), $this->option('limit') ? (int) $this->option('limit') : null);
        }
        if ($all || $this->option('sitemap')) {
            $builder->buildSitemap();
        }
        if ($all || $this->option('assets')) {
            $builder->syncAssets('rest'); // images etc. last: slow first upload, rarely change
        }
        $s = $builder->stats;
        $this->info(sprintf('done in %.1f s: %d uploaded (%.1f MB), %d unchanged, %d failed', microtime(true) - $t0, $s['uploaded'], $s['bytes'] / 1048576, $s['unchanged'], $s['failed']));

        return $s['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function parseIds(?string $spec): ?array
    {
        if (! $spec) {
            return null;
        }
        $ids = [];
        foreach (explode(',', $spec) as $part) {
            if (preg_match('/^(\d+)-(\d+)$/', trim($part), $m)) {
                $ids = array_merge($ids, range((int) $m[1], (int) $m[2]));
            } elseif (ctype_digit(trim($part))) {
                $ids[] = (int) trim($part);
            }
        }

        return $ids;
    }
}
