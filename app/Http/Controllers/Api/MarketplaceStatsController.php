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
                'total' => Sale::notBlocked()->selectRaw('sum(price) as total')->first()->total,
                'avg' => Sale::notBlocked()->selectRaw('avg(price) as avg')->first()->avg,
                'count' => Sale::notBlocked()->selectRaw('count(price) as count')->first()->count,
                'max' => Sale::notBlocked()->selectRaw('max(price) as max')->first()->max
            ];
        });
    }
}
