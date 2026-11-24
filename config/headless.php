<?php

/*
 * Headless frontend (static HTML on R2 served by the Cloudflare Worker "sportvatar-headless").
 * Architecture: docs/HEADLESS.md (same design as flovatar-web; kb notes/headless-architecture.md).
 *
 * The static pages are rendered by this app (Blade -> HTML, `php artisan headless:build`) and uploaded to
 * the R2 bucket `sportvatar-web` under {prefix}site/...; the Worker serves them and proxies the dynamic paths
 * (/api/*, /login*, /logout, /csrf-token, searches, packs, Sportbit pages, ...) to this app on the same origin.
 */
return [
    // Master switch: regeneration jobs (model observers + indexer hook) and scheduled rebuilds only run when true.
    'enabled' => env('HEADLESS_ENABLED', false),

    // Public URL of the static frontend (canonical/og:url/sitemap). Testnet: https://next-testnet.sportvatar.com
    'frontend_url' => rtrim((string) env('HEADLESS_FRONTEND_URL', env('APP_URL', 'http://localhost')), '/'),

    // Storage for the static site: disk + key prefix. Testnet MUST use 'testnet/' (enforced off mainnet).
    'disk' => env('HEADLESS_DISK', 'r2web'),
    'prefix' => env('HEADLESS_PREFIX', ''),

    // Seconds to wait before re-rendering an item page after a change (coalesces bursts of events).
    'debounce' => (int) env('HEADLESS_DEBOUNCE', 30),

    // Queue for the per-item regeneration job.
    'queue' => env('HEADLESS_QUEUE', 'default'),

    // Cache-Control for HTML objects on R2 (the Worker passes it through). Edge TTL 60 s: no purge needed.
    'html_cache_control' => 'public, max-age=0, s-maxage=60, stale-while-revalidate=300',
    'asset_cache_control' => 'public, max-age=86400',
    'versioned_asset_cache_control' => 'public, max-age=31536000, immutable',

    // public/ entries that are never uploaded as static assets.
    'asset_exclude' => ['index.php', 'storage', '.htaccess', 'web.config', 'hot', 'robots.txt', 'mix-manifest.json.bak'],
];
