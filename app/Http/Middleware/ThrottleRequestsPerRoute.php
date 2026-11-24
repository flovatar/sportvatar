<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;

/**
 * Same as Laravel's Redis throttle, but plain `throttle:N,M` limits are counted per route
 * (and IP/user). Laravel's default key is only domain|ip, so every throttled route shared
 * one counter: visiting a few pages (each calls /login/nonce) used up the 5/min budget of
 * /subscribe or the 10/min of POST /login.
 */
class ThrottleRequestsPerRoute extends ThrottleRequestsWithRedis
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        $suffix = $route ? '|'.implode(',', $route->methods()).'|'.$route->uri() : '';

        return sha1(parent::resolveRequestSignature($request).$suffix);
    }
}
