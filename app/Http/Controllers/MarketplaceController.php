<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Sale;
use App\Models\SaleOffer;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Cache;

class MarketplaceController extends FilteredController
{
    public function __invoke()
    {
        $templates = $this->getTemplates()->makeHidden(['flow_metadata', 'svg']);


        $stats = Cache::remember('db-sales-stats', now()->addMinutes(1), function() {
            return [
                'total' => Sale::selectRaw('sum(price) as total')->first()->total,
                'avg' => Sale::selectRaw('avg(price) as avg')->first()->avg,
                'count' => Sale::selectRaw('count(price) as count')->first()->count,
                'max' => Sale::selectRaw('max(price) as max')->first()->max
            ];
        });
        return view('marketplace')->with(['templates' => $templates, 'stats' => $stats]);
    }
}
