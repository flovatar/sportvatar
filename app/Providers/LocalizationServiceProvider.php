<?php

namespace App\Providers;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * Localized routes (EN without prefix, /it and /es), replacing codezero/laravel-localized-routes,
 * which has no Laravel 11/12 release. Same behaviour as the legacy site:
 *
 *  - Route::localized(fn () => ...) registers the routes once per locale. Every copy is named
 *    "{locale}.{name}" (en.builder, it.builder, es.builder) and sets the app locale.
 *  - route('builder') resolves to the copy of the current locale (falls back to config
 *    localized-routes.fallback_locale).
 *  - Route::localizedUrl('it') returns the URL of the current page in another locale; pages
 *    that are not localized return their own URL with ?lang={locale}.
 *  - Non-localized pages use ?lang=, then the locale chosen last (session), then the browser language
 *    (middleware App\Http\Middleware\SetLocale).
 */
class LocalizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::macro('localized', function (Closure $callback) {
            $omit = config('localized-routes.omit_url_prefix_for_locale');
            foreach (LocalizationServiceProvider::locales() as $locale) {
                $attributes = ['as' => $locale.'.', 'middleware' => 'locale:'.$locale];
                if ($locale !== $omit) {
                    $attributes['prefix'] = $locale;
                }
                Route::group($attributes, $callback);
            }
        });

        Route::macro('localizedUrl', function (?string $locale = null, $parameters = null, bool $absolute = true) {
            $locale = $locale ?: app()->getLocale();
            $route = Route::current();
            $base = $route ? LocalizationServiceProvider::baseName($route->getName()) : null;
            if ($base === null || ! Route::has($locale.'.'.$base)) {
                // Not localized: same URL, ?lang= switches the remembered locale (SetLocale).
                $url = $absolute ? url()->current() : '/'.ltrim(request()->path(), '/');

                return $url.'?lang='.$locale;
            }

            return URL::route($locale.'.'.$base, $parameters ?? $route->parameters(), $absolute);
        });

        URL::resolveMissingNamedRoutesUsing(function ($name, $parameters, $absolute) {
            if (! is_string($name)) {
                return null;
            }
            foreach (array_unique([app()->getLocale(), config('localized-routes.fallback_locale', 'en')]) as $locale) {
                if (Route::has($locale.'.'.$name)) {
                    return URL::route($locale.'.'.$name, $parameters, $absolute);
                }
            }

            return null;
        });
    }

    public static function locales(): array
    {
        return config('localized-routes.supported-locales', ['en']);
    }

    /** "it.builder" => "builder"; null when the route name is not localized. */
    public static function baseName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }
        $parts = explode('.', $name, 2);

        return (count($parts) === 2 && in_array($parts[0], static::locales(), true)) ? $parts[1] : null;
    }
}
