<?php

namespace App\Services\Headless;

use App\Models\Sportvatar;
use App\Support\Headless;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Builds the static headless site from the app's own Blade views (Laravel = static site generator):
 *  - shells: home, how it works, Builder, packs (EN/IT/ES), marketplace, Locker Room, transactions, privacy,
 *    collection shell, 404
 *  - items: one page per Sportvatar (snapshot + live chain refresh in the browser). Sportbit pages stay
 *    server-rendered (proxied by the Worker, edge-cacheable): 100k+ items on mainnet.
 *  - assets: public/ (css/js from the Mix build of THIS deploy, images, fonts)
 *  - sitemap.xml
 * Pages are rendered by sending an internal GET through the HTTP kernel (same controllers, middleware and
 * views as the live site) inside a Headless context, then written to the `headless.disk` under
 * Headless::prefix() (testnet: testnet/site/...). Unchanged objects (same md5/ETag) are not re-uploaded.
 */
class StaticSiteBuilder
{
    /** Shell pages: request path => [page id, object key (relative to the site prefix), shell?] */
    public const SHELLS = [
        '/' => ['home', 'index.html'],
        '/it' => ['home-it', 'it/index.html'],
        '/es' => ['home-es', 'es/index.html'],
        '/how-it-works' => ['getstarted', 'how-it-works/index.html'],
        '/it/how-it-works' => ['getstarted-it', 'it/how-it-works/index.html'],
        '/es/how-it-works' => ['getstarted-es', 'es/how-it-works/index.html'],
        '/builder' => ['builder', 'builder/index.html'],
        '/it/builder' => ['builder-it', 'it/builder/index.html'],
        '/es/builder' => ['builder-es', 'es/builder/index.html'],
        '/packs' => ['packs', 'packs/index.html'],
        '/it/packs' => ['packs-it', 'it/packs/index.html'],
        '/es/packs' => ['packs-es', 'es/packs/index.html'],
        '/marketplace' => ['marketplace', 'marketplace/index.html'],
        '/locker-room' => ['locker-room', 'locker-room/index.html'],
        '/transactions' => ['transactions', 'transactions/index.html'],
        '/hall-of-fame' => ['hall-of-fame', 'hall-of-fame/index.html'],
        '/privacy' => ['privacy', 'privacy/index.html'],
        '/privacy-it' => ['privacy-it', 'privacy-it/index.html'],
        // shells whose address comes from the URL at runtime (Worker: /collection/0x... -> this object)
        '/collection/0x0000000000000000' => ['collection', '_shells/collection.html', true],
        '/__headless_404' => ['404', '404.html'],
    ];

    public const ITEM_TYPES = ['sportvatar' => Sportvatar::class];

    private ?array $etags = null;

    private array $sizes = [];

    public array $stats = ['uploaded' => 0, 'unchanged' => 0, 'failed' => 0, 'bytes' => 0];

    /** @var callable|null */
    public $log = null;

    public function __construct(private ?string $outDir = null, private bool $force = false, private bool $dryRun = false)
    {
    }

    public static function itemKey(string $type, int $id): string
    {
        return ItemSnapshot::SEGMENTS[$type].'/'.$id.'/index.html';
    }

    // ---------------------------------------------------------------- rendering

    /** Render one path through the HTTP kernel inside a Headless context. */
    public function render(string $path, array $context): array
    {
        $front = parse_url((string) config('headless.frontend_url'));
        $server = ['HTTP_HOST' => $front['host'] ?? 'localhost', 'HTTPS' => ($front['scheme'] ?? 'https') === 'https' ? 'on' : 'off',
            'HTTP_USER_AGENT' => 'sportvatar-static-build', 'REMOTE_ADDR' => '127.0.0.1'];
        $request = Request::create($path, 'GET', [], [], [], $server);
        $kernel = app(HttpKernel::class);
        Headless::begin($context + ['path' => $path]);
        $locale = app()->getLocale();
        app()->setLocale((string) config('localized-routes.fallback_locale', 'en'));
        try {
            app()->instance('request', $request);
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);
        } finally {
            Headless::end();
            app()->setLocale($locale);
        }
        $status = $response->getStatusCode();
        $html = (string) $response->getContent();
        if ($status >= 500 || ($status !== 200 && ($context['page'] ?? '') !== '404')) {
            throw new RuntimeException("render {$path}: HTTP {$status}");
        }

        return ['status' => $status, 'html' => $html];
    }

    public function buildShells(?array $only = null): void
    {
        foreach (self::SHELLS as $path => $def) {
            [$page, $key] = $def;
            if ($only && ! in_array($page, $only, true)) {
                continue;
            }
            $this->guard("shell {$page}", function () use ($path, $page, $key, $def) {
                $r = $this->render($path, ['page' => $page, 'shell' => (bool) ($def[2] ?? false), 'canonical' => $page === 'collection' ? '/collection' : $path]);
                $this->assertSessionless($r['html'], $page);
                $this->put($key, $r['html'], 'text/html; charset=utf-8', (string) config('headless.html_cache_control'));
            });
        }
    }

    /** Build (or rebuild) one item page. Returns 'uploaded' | 'unchanged' | 'missing'. */
    public function buildItem(string $type, int $id): string
    {
        $snapshot = ItemSnapshot::build($type, $id);
        if (! $snapshot || ! $snapshot['owner']) {
            $this->delete(self::itemKey($type, $id));

            return 'missing';
        }
        $path = '/'.ItemSnapshot::SEGMENTS[$type].'/'.$id.'/'.$snapshot['owner'];
        $r = $this->render($path, [
            'page' => $type,
            'canonical' => '/'.ItemSnapshot::SEGMENTS[$type].'/'.$id,
            'item' => ['type' => $type, 'id' => $id, 'owner' => $snapshot['owner'], 'snapshot' => $snapshot],
        ]);
        $this->assertSessionless($r['html'], $type.' #'.$id);

        return $this->put(self::itemKey($type, $id), $r['html'], 'text/html; charset=utf-8', (string) config('headless.html_cache_control'));
    }

    public function buildItems(?string $onlyType = null, ?array $ids = null, ?int $limit = null): void
    {
        foreach (self::ITEM_TYPES as $type => $class) {
            if ($onlyType && $onlyType !== $type) {
                continue;
            }
            $query = $class::query()->whereNotNull('flow_id')->whereNotNull('flow_address')->orderBy('flow_id');
            if ($ids) {
                $query->whereIn('flow_id', $ids);
            }
            if ($limit) {
                $query->limit($limit);
            }
            foreach ($query->pluck('flow_id') as $id) {
                $this->guard("{$type} #{$id}", fn () => $this->buildItem($type, (int) $id));
            }
        }
    }

    // ---------------------------------------------------------------- assets + sitemap

    /** Bundle first (js/css/manifest/fonts, small), so pages never reference a bundle that isn't uploaded yet. */
    public const PRIORITY_ASSET_DIRS = ['js', 'css', 'fonts', 'mix-manifest.json'];

    /** $scope: 'all' | 'bundle' (js/css/fonts) | 'rest' (everything else, e.g. images) */
    public function syncAssets(string $scope = 'all'): void
    {
        $public = public_path();
        $exclude = (array) config('headless.asset_exclude');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($public, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS));
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            $rel = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($public))), '/');
            $top = explode('/', $rel)[0];
            if (in_array($top, $exclude, true) || in_array($rel, $exclude, true) || str_starts_with(basename($rel), '.')) {
                continue;
            }
            $priority = in_array($top, self::PRIORITY_ASSET_DIRS, true);
            if (($scope === 'bundle' && ! $priority) || ($scope === 'rest' && $priority)) {
                continue;
            }
            $this->guard("asset {$rel}", fn () => $this->putFile($rel, $file->getPathname()));
        }
    }

    /**
     * Keep every deployed bundle version: js/app.js@{id}, css/app.css@{id} (ids from mix-manifest.json). The Worker serves
     * /js/app.js?id=X from the matching copy, so a page built before a deploy keeps the bundle it was built with
     * (immutable caching stays correct) and new pages get the new one.
     */
    public function syncVersionedBundle(): void
    {
        $manifest = public_path('mix-manifest.json');
        if (! is_file($manifest)) {
            return;
        }
        foreach ((array) json_decode((string) file_get_contents($manifest), true) as $asset => $versioned) {
            if (! preg_match('/[?&]id=([a-f0-9]+)/', (string) $versioned, $m)) {
                continue;
            }
            $rel = ltrim($asset, '/');
            $file = public_path($rel);
            if (! is_file($file)) {
                continue;
            }
            $key = $rel.'@'.$m[1];
            $this->guard("bundle {$key}", function () use ($key, $file, $rel) {
                if ($this->outDir || $this->currentEtag($this->fullKey($key)) !== md5_file($file)) {
                    $this->put($key, (string) file_get_contents($file), self::mime($rel), (string) config('headless.versioned_asset_cache_control'));
                } else {
                    $this->stats['unchanged']++;
                }
            });
        }
    }

    public function buildSitemap(): void
    {
        $urls = [];
        foreach (self::SHELLS as $path => $def) {
            if (($def[2] ?? false) || $def[0] === '404') {
                continue;
            }
            $urls[] = Headless::frontendUrl($path);
        }
        foreach (self::ITEM_TYPES as $type => $class) {
            foreach ($class::query()->whereNotNull('flow_id')->whereNotNull('flow_address')->orderBy('flow_id')->pluck('flow_id') as $id) {
                $urls[] = Headless::frontendUrl(ItemSnapshot::SEGMENTS[$type].'/'.$id);
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.htmlspecialchars($u, ENT_XML1).'</loc></url>'."\n";
        }
        $xml .= '</urlset>'."\n";
        $this->put('sitemap.xml', $xml, 'application/xml; charset=utf-8', 'public, max-age=3600');
    }

    // ---------------------------------------------------------------- storage

    private function disk()
    {
        return Storage::disk((string) config('headless.disk'));
    }

    private function fullKey(string $key): string
    {
        return Headless::prefix().ltrim($key, '/');
    }

    /** ETags of every object under the prefix (one paginated listing per run; only for bulk builds). */
    public function loadEtags(): void
    {
        if ($this->outDir || $this->etags !== null) {
            return;
        }
        $this->etags = [];
        $client = $this->disk()->getClient();
        $bucket = (string) config('filesystems.disks.'.config('headless.disk').'.bucket');
        $token = null;
        do {
            $res = $client->listObjectsV2(array_filter(['Bucket' => $bucket, 'Prefix' => Headless::prefix(), 'ContinuationToken' => $token]));
            foreach ($res['Contents'] ?? [] as $o) {
                $this->etags[$o['Key']] = trim((string) $o['ETag'], '"');
                $this->sizes[$o['Key']] = (int) ($o['Size'] ?? -1);
            }
            $token = ! empty($res['IsTruncated']) ? $res['NextContinuationToken'] : null;
        } while ($token);
    }

    private function currentEtag(string $full): ?string
    {
        if ($this->etags !== null) {
            return $this->etags[$full] ?? null;
        }
        try {
            $client = $this->disk()->getClient();
            $bucket = (string) config('filesystems.disks.'.config('headless.disk').'.bucket');
            $head = $client->headObject(['Bucket' => $bucket, 'Key' => $full]);

            return trim((string) $head['ETag'], '"');
        } catch (Throwable $e) {
            return null;
        }
    }

    public function put(string $key, string $body, string $type, string $cacheControl): string
    {
        $full = $this->fullKey($key);
        if ($this->outDir) {
            $path = rtrim($this->outDir, '/').'/'.$full;
            @mkdir(dirname($path), 0775, true);
            if (! $this->force && is_file($path) && md5_file($path) === md5($body)) {
                $this->stats['unchanged']++;

                return 'unchanged';
            }
            file_put_contents($path, $body);
        } else {
            if (! $this->force && $this->currentEtag($full) === md5($body)) {
                $this->stats['unchanged']++;

                return 'unchanged';
            }
            if (! $this->dryRun) {
                $this->disk()->put($full, $body, ['ContentType' => $type, 'CacheControl' => $cacheControl]);
            }
        }
        $this->stats['uploaded']++;
        $this->stats['bytes'] += strlen($body);
        $this->say("put {$full} (".strlen($body).' B)');

        return 'uploaded';
    }

    private function putFile(string $rel, string $path): string
    {
        $full = $this->fullKey($rel);
        $md5 = md5_file($path);
        if ($this->outDir) {
            $dest = rtrim($this->outDir, '/').'/'.$full;
            if (! $this->force && is_file($dest) && md5_file($dest) === $md5) {
                $this->stats['unchanged']++;

                return 'unchanged';
            }
            @mkdir(dirname($dest), 0775, true);
            copy($path, $dest);
        } else {
            $etag = $this->currentEtag($full);
            $multipartSame = $etag !== null && str_contains($etag, '-') && ($this->sizes[$full] ?? -1) === filesize($path);
            if (! $this->force && ($etag === $md5 || $multipartSame)) {
                $this->stats['unchanged']++;

                return 'unchanged';
            }
            if (! $this->dryRun) {
                $opts = ['ContentType' => self::mime($rel), 'CacheControl' => (string) config('headless.asset_cache_control')];
                if (filesize($path) < 64 * 1048576) {
                    $this->disk()->put($full, file_get_contents($path), $opts); // single PUT: ETag = md5 (skip check works)
                } else {
                    $stream = fopen($path, 'rb');
                    try {
                        $this->disk()->writeStream($full, $stream, $opts);
                    } finally {
                        is_resource($stream) && fclose($stream);
                    }
                }
            }
        }
        $this->stats['uploaded']++;
        $this->stats['bytes'] += filesize($path);
        $this->say("put {$full}");

        return 'uploaded';
    }

    public function delete(string $key): void
    {
        $full = $this->fullKey($key);
        if ($this->outDir) {
            @unlink(rtrim($this->outDir, '/').'/'.$full);

            return;
        }
        if (! $this->dryRun && $this->currentEtag($full) !== null) {
            $this->disk()->delete($full);
            $this->say("delete {$full}");
        }
    }

    public static function mime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return [
            'html' => 'text/html; charset=utf-8', 'css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json', 'xml' => 'application/xml', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'ico' => 'image/x-icon', 'woff' => 'font/woff',
            'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'eot' => 'application/vnd.ms-fontobject', 'mp4' => 'video/mp4',
            'webm' => 'video/webm', 'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'pdf' => 'application/pdf', 'txt' => 'text/plain; charset=utf-8',
            'webmanifest' => 'application/manifest+json', 'glb' => 'model/gltf-binary', 'gltf' => 'model/gltf+json', 'map' => 'application/json',
        ][$ext] ?? 'application/octet-stream';
    }

    /** Static HTML must be user-agnostic: no CSRF token / session data may leak into an object on R2. */
    private function assertSessionless(string $html, string $page): void
    {
        if (preg_match('/name="csrf-token"|name="_token"/', $html)) {
            throw new RuntimeException("{$page}: rendered HTML contains a CSRF token");
        }
    }

    private function guard(string $what, callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            $this->stats['failed']++;
            $this->say("FAILED {$what}: ".mb_substr($e->getMessage(), 0, 300), true);
        }
    }

    private function say(string $msg, bool $error = false): void
    {
        if ($this->log) {
            ($this->log)($msg, $error);
        }
    }
}
