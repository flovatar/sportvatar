<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Sale;
use Illuminate\Http\Request;
use Cache;

class SportvatarController extends Controller
{
    public function __invoke($sportvatarId, $address)
    {

        $templates = $this->getTemplates();
        $series = $this->getSeries();
        $sales = Cache::remember('sportvatar-sales-'.$sportvatarId, now()->addMinutes(10), function() use ($sportvatarId) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            return $sportvatar ? $sportvatar->sales()->orderBy('created_at', 'asc')->get() : [];
        });
        $saleOffer = Cache::remember('sportvatar-sale-offers-'.$sportvatarId.'-'.$address, now()->addMinutes(1), function() use ($sportvatarId, $address) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            return $sportvatar ? $sportvatar->saleOffers()->notBlocked()->whereFlowAddress($address)->where('is_completed', false)->whereNotNull('listing_id')->whereIn('currency', ['DUC', 'FLOW'])->orderBy('created_at', 'desc')->first() : null;
        });
        return view('sportvatar')->with(['templates' => $templates, 'series' => $series, 'address' => $address, 'sportvatarId' => $sportvatarId, 'sportvatarSales' => $sales, 'sportvatarSaleOffer' => $saleOffer]);
    }
}
