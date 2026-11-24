<?php

namespace App\Support;

/**
 * Static-render context for the headless frontend (`headless:build`, RegenerateStaticItem).
 *
 * While a page is rendered for R2, views ask Headless::building() to switch the few places that differ
 * from the server-rendered site: canonical URL, the window.HEADLESS bootstrap object, and shells whose
 * address comes from the URL at runtime (collection). Outside a build every method is a no-op, so the
 * legacy site renders exactly as before.
 */
class Headless
{
    private static ?array $context = null;

    /** @param array{page:string, path:string, shell?:bool, item?:array|null} $context */
    public static function begin(array $context): void
    {
        self::$context = $context + ['shell' => false, 'item' => null];
    }

    public static function end(): void
    {
        self::$context = null;
    }

    public static function building(): bool
    {
        return self::$context !== null;
    }

    public static function shell(): bool
    {
        return (bool) (self::$context['shell'] ?? false);
    }

    public static function context(): ?array
    {
        return self::$context;
    }

    /** Canonical URL of the page being built (frontend host, owner-less Sportvatar URLs). */
    public static function canonicalUrl(): string
    {
        return self::frontendUrl((string) (self::$context['canonical'] ?? self::$context['path'] ?? '/'));
    }

    public static function frontendUrl(string $path = ''): string
    {
        $base = (string) config('headless.frontend_url');

        return $path === '' || $path === '/' ? $base.'/' : $base.'/'.ltrim($path, '/');
    }

    /** Storage prefix for the static site; off mainnet always under testnet/ (never overwrite prod objects). */
    public static function prefix(): string
    {
        $prefix = trim((string) config('headless.prefix'), '/');
        if (config('services.flow.network') !== 'mainnet') {
            if ($prefix === '' || ! str_starts_with($prefix, 'testnet')) {
                $prefix = 'testnet'.($prefix === '' ? '' : '/'.$prefix);
            }
        }

        return ($prefix === '' ? '' : $prefix.'/').'site/';
    }

    /**
     * JS bootstrap object (window.HEADLESS) for a statically rendered page.
     * apiBase '' = same origin (the Worker proxies /api/* to this app).
     */
    public static function bootstrap(): array
    {
        $c = self::$context ?? [];

        return [
            'mode' => 'static',
            'page' => $c['page'] ?? null,
            'path' => ! empty($c['shell']) ? ($c['canonical'] ?? null) : ($c['path'] ?? null), // shells: no placeholder address
            'canonical' => $c['canonical'] ?? ($c['path'] ?? '/'),
            'shell' => (bool) ($c['shell'] ?? false),
            'item' => $c['item'] ?? null,
            'apiBase' => '',
            'frontendUrl' => (string) config('headless.frontend_url'),
            'imagesUrl' => (string) config('render.public_url'),
            'blocked' => array_values(Blocklist::addresses()),
        ];
    }
}
