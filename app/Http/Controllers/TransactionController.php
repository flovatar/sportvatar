<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Sale;
use Illuminate\Http\Request;
use Cache;

class TransactionController extends Controller
{
    public function __invoke($address = null)
    {

        $templates = $this->getTemplates()->makeHidden(['flow_metadata', 'svg']);


        $stats = Cache::remember('db-sales-stats', now()->addMinutes(1), function() {
            return [
                'total' => Sale::notBlocked()->selectRaw('sum(price) as total')->first()->total,
                'avg' => Sale::notBlocked()->selectRaw('avg(price) as avg')->first()->avg,
                'count' => Sale::notBlocked()->selectRaw('count(price) as count')->first()->count,
                'max' => Sale::notBlocked()->selectRaw('max(price) as max')->first()->max
                ];
        });


        return view('transactions')->with(['address' => $address, 'templates' => $templates, 'stats' => $stats]);
    }
}
