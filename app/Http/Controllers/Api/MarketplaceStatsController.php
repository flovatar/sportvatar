<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FilteredController;
use App\Models\Sale;
use Cache;

class MarketplaceStatsController extends FilteredController
{
    public function __invoke()
    {
        return Cache::remember('db-sales-stats', now()->addMinutes(1), function() {
            return [
                'total' => Sale::notBlocked()->usd()->selectRaw('sum(price) as total')->first()->total,
                'avg' => Sale::notBlocked()->usd()->selectRaw('avg(price) as avg')->first()->avg,
                'count' => Sale::notBlocked()->usd()->selectRaw('count(price) as count')->first()->count,
                'max' => Sale::notBlocked()->usd()->selectRaw('max(price) as max')->first()->max
            ];
        });
    }
}
