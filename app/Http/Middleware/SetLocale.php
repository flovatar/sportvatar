<?php

namespace App\Http\Middleware;

use App\Providers\LocalizationServiceProvider;
use App\Support\PublicCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Sets the app locale.
 *  - Localized routes ('locale:it' route middleware): the locale of the route, remembered in the session.
 *  - Other pages (web group): ?lang=, then the remembered locale, else the browser language, else the default.
 *  - Stateless requests (anonymous, edge-cached pages, App\Support\PublicCache): never the browser language
 *    (the edge cache is keyed by URL only): ?lang= or the default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $routeLocale = null)
    {
        $supported = LocalizationServiceProvider::locales();

        if ($routeLocale !== null && in_array($routeLocale, $supported, true)) {
            $locale = $routeLocale;
            if ($request->hasSession()) {
                $request->session()->put('locale', $locale);
            }
        } elseif (in_array($request->query('lang'), $supported, true)) {
            $locale = $request->query('lang');
            if ($request->hasSession()) {
                $request->session()->put('locale', $locale);
            }
        } else {
            $locale = $request->hasSession() ? $request->session()->get('locale') : null;
            if (! in_array($locale, $supported, true) && PublicCache::isStateless($request)) {
                $locale = config('app.locale', 'en');
            } elseif (! in_array($locale, $supported, true)) {
                $locale = $request->getPreferredLanguage($supported) ?: config('app.locale', 'en');
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
