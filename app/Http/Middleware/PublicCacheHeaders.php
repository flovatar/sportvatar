<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Outermost middleware of the web group: sets Cache-Control for the edge cache.
 *  - stateless public page (anonymous GET): no cookies at all + PublicCache::PUBLIC_HEADER
 *  - stateless other request: keeps the controller's headers (e.g. login nonce cookie)
 *  - request with a session: Cache-Control private, no-store (unless the controller
 *    explicitly marked the response public, e.g. CDN redirects)
 */
class PublicCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        if (PublicCache::isPublicPage($request) && in_array($response->getStatusCode(), PublicCache::CACHEABLE_STATUS, true)) {
            foreach ($response->headers->getCookies() as $cookie) {
                $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
            if (! $this->explicitlyPublic($response)) {
                $response->headers->set('Cache-Control', PublicCache::PUBLIC_HEADER);
            }
            $response->headers->set('X-Edge-Cacheable', '1');

            return $response;
        }

        if (PublicCache::isStateless($request)) {
            if (! $response->headers->hasCacheControlDirective('no-store') && ! $this->explicitlyPublic($response)) {
                $response->headers->set('Cache-Control', PublicCache::PRIVATE_HEADER);
            }

            return $response;
        }

        if (! $this->explicitlyPublic($response)) {
            $response->headers->set('Cache-Control', PublicCache::PRIVATE_HEADER);
        }

        return $response;
    }

    protected function explicitlyPublic(Response $response): bool
    {
        return $response->headers->hasCacheControlDirective('public');
    }
}
