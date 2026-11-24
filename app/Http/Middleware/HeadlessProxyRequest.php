<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Requests proxied by the headless Worker (sportvatar-headless, docs/HEADLESS.md).
 *
 * 1. Client IP. Every Worker subrequest reaches the origin from Cloudflare's Workers address
 *    (2a06:98c0:3600::103), so without this all visitors share ONE IP for every per-IP limit
 *    (`/login/nonce` 60/min is called on every page view, POST /login 10/min, the api group 60/min).
 *    The Worker sends the visitor's CF-Connecting-IP as `X-Headless-Client-IP` (+ `X-Headless-Proxy: 1`);
 *    it is trusted only when the connection itself comes from the Workers range (nginx already resolved
 *    the Cloudflare hop via real_ip). Anyone else sending the header is ignored.
 * 2. Origin host (e.g. api.sportvatar.com, the Worker's ORIGIN): direct hits get `X-Robots-Tag: noindex`
 *    so the server-rendered copy of the site is never indexed next to sportvatar.com. Requests through the
 *    Worker don't (it serves them on the frontend host; the Worker also drops the header).
 */
class HeadlessProxyRequest
{
    public function handle(Request $request, Closure $next)
    {
        $viaWorker = self::fromWorker($request);
        if ($viaWorker) {
            $ip = trim((string) $request->headers->get('X-Headless-Client-IP', ''));
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $request->server->set('REMOTE_ADDR', $ip);
            }
        }

        $response = $next($request);

        if (! $viaWorker && self::isOriginHost($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    public static function fromWorker(Request $request): bool
    {
        if ($request->headers->get('X-Headless-Proxy') !== '1') {
            return false;
        }
        $remote = (string) $request->server->get('REMOTE_ADDR', '');
        $ranges = (array) config('headless.worker_ips', []);

        return $remote !== '' && $ranges && IpUtils::checkIp($remote, $ranges);
    }

    public static function isOriginHost(Request $request): bool
    {
        $hosts = (array) config('headless.origin_hosts', []);

        return $hosts && in_array(strtolower($request->getHost()), $hosts, true);
    }
}
