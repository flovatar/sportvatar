<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bearer-token auth for the render worker API (/api/render-jobs*). The token lives only in the server .env
 * (RENDER_WORKER_TOKEN) and in a root-owned env file on the agent box. No token configured = API off (404).
 * Responses are never cacheable (no session, no cookies: the routes sit in the stateless 'api' group).
 */
class AuthenticateRenderWorker
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) config('render.worker_token');
        if (strlen($token) < 32) {
            abort(404);
        }
        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['error' => 'unauthorized'], 401)->header('Cache-Control', 'no-store, private');
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
