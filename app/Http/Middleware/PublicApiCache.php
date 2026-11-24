<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public read API (/api/v2, GET): shared-cache headers + CORS for any origin (no credentials).
 * Cloudflare (or the Worker's cache) keeps a response 60 s and serves it stale for 5 min while
 * revalidating, so the origin sees ~1 request per unique URL per minute. Errors (5xx) are never cached.
 */
class PublicApiCache
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($this->cors() + ['Access-Control-Max-Age' => '86400']);
        }
        $request->headers->set('Accept', 'application/json'); // errors as JSON, never HTML
        $response = $next($request);
        foreach ($this->cors() as $k => $v) {
            $response->headers->set($k, $v);
        }
        $status = $response->getStatusCode();
        if (in_array($request->getMethod(), ['GET', 'HEAD'], true) && in_array($status, PublicCache::CACHEABLE_STATUS, true)) {
            $response->headers->set('Cache-Control', PublicCache::PUBLIC_HEADER);
        } else {
            $response->headers->set('Cache-Control', PublicCache::PRIVATE_HEADER);
        }
        $response->headers->remove('Set-Cookie');

        return $response;
    }

    private function cors(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ];
    }
}
