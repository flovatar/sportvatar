<?php

/*
| Static image renderer (SVG -> PNG/JPG) for item pages, downloads and share previews.
| Server: resvg (static binary, no Chromium). Combo CSS (`#A ~ #B`, @keyframes) is pre-resolved by
| App\Services\Render\SvgFlattener, so CSS sibling-selector combos render. Output goes to R2 (disk below) under
| immutable, content-hashed keys: {prefix}{type}/r/{flow_id}/{hash}/{variant}.
*/
return [
    'enabled' => (bool) env('RENDER_ENABLED', false),

    // resvg binary (https://github.com/linebender/resvg, release tarball resvg-linux-x86_64)
    'resvg' => env('RENDER_RESVG_BIN', '/home/forge/bin/resvg'),
    // Optional fonts directory (item SVGs contain no <text> today). Licensed fonts stay outside git.
    'fonts_dir' => env('RENDER_FONTS_DIR'),
    // CPU/RAM guards for the rasteriser process (the web server shares the box with PHP-FPM).
    'nice' => (int) env('RENDER_NICE', 15),
    'memory_mb' => (int) env('RENDER_MEMORY_MB', 768),
    'timeout' => (int) env('RENDER_TIMEOUT', 60),

    'disk' => env('RENDER_DISK', 'r2'),
    // Key prefix. Testnet MUST use 'testnet/' (enforced when services.flow.network != mainnet).
    'prefix' => env('RENDER_PREFIX', ''),
    // Public base URL of the bucket (custom domain).
    'public_url' => rtrim((string) env('RENDER_PUBLIC_URL', 'https://images.sportvatar.com'), '/'),
    // When an item has no new render yet, fall back to the legacy CDN object (prod only; testnet ids
    // don't exist on the prod CDN).
    'legacy_fallback' => (bool) env('RENDER_LEGACY_FALLBACK', true),
    // Disk that holds the legacy exports (bucket listing for render:images --missing).
    'legacy_disk' => env('RENDER_LEGACY_DISK', 'r2'),

    'size' => 1000,
    'thumb' => 400,
    'og' => [1200, 630],
    'jpeg_quality' => 88,
    'cache_control' => 'public, max-age=31536000, immutable',

    // Bump to re-render everything (changes every content hash).
    'version' => 1,

    // Browser (Chromium) render worker on the agent box: pulls jobs from /api/render-jobs with this bearer
    // token (server .env only, never committed; empty = job API disabled -> 404), renders PNG/JPG/GIF/MP4
    // and uploads them itself to {prefix}{type}/r/{id}/{hash}-b/ on the render bucket.
    'worker_token' => env('RENDER_WORKER_TOKEN'),
    'jobs' => [
        // enqueue a browser job whenever the server renders a new item state (mint/update events)
        'enabled' => (bool) env('RENDER_JOBS_ENABLED', true),
        'lease_seconds' => (int) env('RENDER_JOBS_LEASE', 900),
        'max_attempts' => 4,
        // item types the browser worker does NOT render (Luca 2026-11-24: templates are delivered as SVG everywhere;
        // their share-preview JPG comes from the server render) - comma separated
        'skip_types' => array_values(array_filter(array_map('trim', explode(',', (string) env('RENDER_BROWSER_SKIP_TYPES', 'template'))))),
    ],
    // Bucket of the 'r2' disk (told to the worker; it only accepts its own bucket + prefix).
    'bucket' => env('R2_IMAGES_BUCKET', 'sportvatar'),
];
