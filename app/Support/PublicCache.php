<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Edge-cacheable anonymous pages (same design as flovatar-web, see docs/HEADLESS.md).
 *
 * A request is "stateless" when it can be answered without a Laravel session:
 *  - anonymous GET/HEAD (no session cookie, no remember cookie) to any web route that
 *    does not need a session (see SESSION_PATHS), or
 *  - GET/HEAD to a path that never needs a session (ALWAYS_STATELESS_PATHS), or
 *  - an anonymous read-only search POST (READONLY_POST_PATHS, CSRF-exempt).
 * Stateless requests get an in-memory session that is never saved and no cookies are
 * set (except the encrypted login-nonce cookie issued by /login/nonce).
 *
 * Stateless GETs to PUBLIC_PATHS get
 *   Cache-Control: public, max-age=0, s-maxage=60, stale-while-revalidate=300
 * so Cloudflare (or the headless Worker) can cache them for 60 s. Everything else that
 * carries a session gets Cache-Control: private, no-store.
 *
 * Locale: stateless requests never look at Accept-Language (the edge cache is keyed by URL only):
 * localized routes (/, /it, /es, ...) carry their locale, other pages use ?lang= or the default (en).
 */
class PublicCache
{
    public const ATTRIBUTE = 'sportvatar.stateless';

    public const PUBLIC_HEADER = 'public, max-age=0, s-maxage=60, stale-while-revalidate=300';

    public const PRIVATE_HEADER = 'private, no-store';

    /** Paths (Request::is patterns) that always need a real session. Checked first. */
    public const SESSION_PATHS = [
        'login', 'logout', 'csrf-token',
        'pack', 'pack/*', 'it/pack', 'it/pack/*', 'es/pack', 'es/pack/*',
        'twitter/*', 'sportvatars/*/*/twitter',
        'treasure-hunting', 'treasure-hunting/*',
        'subscribe', 'graffle',
        'admin', 'admin/*', 'livewire/*', 'filament/*', 'sanctum/*',
        'api/v2/auth/*',
    ];

    /** GET paths that never need a session, even for logged-in users (images, JSON, template data). */
    public const ALWAYS_STATELESS_PATHS = [
        'api/image/*', 'templates/svg/*', 'templates/data/*', 'collection/api', 'collection/api/*',
    ];

    /** Public pages: anonymous GETs get the shared-cache header. */
    public const PUBLIC_PATHS = [
        '/', 'it', 'es',
        'how-it-works', 'it/how-it-works', 'es/how-it-works',
        'meme-maker', 'it/meme-maker', 'es/meme-maker',
        'builder', 'it/builder', 'es/builder',
        'packs', 'it/packs', 'es/packs',
        'marketplace', 'locker-room', 'leaderboard', 'search',
        'sportvatars/*', 'components/*', 'collection/*',
        'transactions', 'transactions/*',
        'privacy', 'privacy-it', 'hall-of-fame', 'mosaic',
        'api/image/*', 'templates/svg/*', 'templates/data/*', 'collection/api', 'collection/api/*',
    ];

    /** Read-only search endpoints called with POST from public pages (no CSRF, no session). */
    public const READONLY_POST_PATHS = [
        'search', 'locker-room/search', 'leaderboard/search', 'transactions/search', 'builder/search',
    ];

    /** Status codes a shared cache may keep for 60 s. */
    public const CACHEABLE_STATUS = [200, 203, 301, 302, 404, 410];

    public static function hasSessionCookie(Request $request): bool
    {
        if ($request->cookies->has(config('session.cookie'))) {
            return true;
        }
        foreach ($request->cookies->keys() as $name) {
            if (str_starts_with($name, 'remember_web_')) {
                return true;
            }
        }

        return false;
    }

    public static function isReading(Request $request): bool
    {
        return in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /** Decide (and remember) whether this request runs without a session. */
    public static function isStateless(Request $request): bool
    {
        if ($request->attributes->has(self::ATTRIBUTE)) {
            return (bool) $request->attributes->get(self::ATTRIBUTE);
        }

        $stateless = self::decide($request);
        $request->attributes->set(self::ATTRIBUTE, $stateless);

        return $stateless;
    }

    protected static function decide(Request $request): bool
    {
        if ($request->is(...self::SESSION_PATHS)) {
            return false;
        }

        if (self::isReading($request)) {
            if ($request->is(...self::ALWAYS_STATELESS_PATHS)) {
                return true;
            }

            return ! self::hasSessionCookie($request);
        }

        if ($request->isMethod('POST') && $request->is(...self::READONLY_POST_PATHS)) {
            return ! self::hasSessionCookie($request);
        }

        return false;
    }

    /** Stateless GET/HEAD to a public page: may be stored by Cloudflare. */
    public static function isPublicPage(Request $request): bool
    {
        return in_array($request->getMethod(), ['GET', 'HEAD'], true)
            && self::isStateless($request)
            && $request->is(...self::PUBLIC_PATHS);
    }
}
