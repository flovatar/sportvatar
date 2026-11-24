<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Illuminate\Session\Store;

/**
 * Laravel's StartSession, except for stateless requests (App\Support\PublicCache):
 * those get a throw-away in-memory session that is never persisted and no session
 * cookie, so anonymous public pages stay cacheable at the edge.
 */
class StartSession extends BaseStartSession
{
    public function handle($request, Closure $next)
    {
        if ($this->sessionConfigured() && PublicCache::isStateless($request)) {
            $session = new Store(
                $this->manager->getSessionConfig()['cookie'] ?? 'laravel_session',
                new ArraySessionHandler((int) ($this->manager->getSessionConfig()['lifetime'] ?? 120))
            );
            $session->start();
            $request->setLaravelSession($session);

            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
