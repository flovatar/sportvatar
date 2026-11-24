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

        $series = $this->getSeries();
        $sales = Cache::remember('sportvatar-sales-'.$sportvatarId, now()->addMinutes(10), function() use ($sportvatarId) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            return $sportvatar ? $sportvatar->sales()->orderBy('created_at', 'asc')->get() : [];
        });
        $saleOffer = Cache::remember('sportvatar-sale-offers-'.$sportvatarId.'-'.$address, now()->addMinutes(1), function() use ($sportvatarId, $address) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            // without flow_metadata: it repeats the whole Sportvatar incl. its SVG (~70 KB) and the page only reads price/listing/currency
            $offer = $sportvatar ? $sportvatar->saleOffers()->notBlocked()->whereFlowAddress($address)->where('is_completed', false)->whereNotNull('listing_id')->whereIn('currency', ['DUC', 'FLOW'])->orderBy('created_at', 'desc')->first() : null;
            return $offer ? $offer->makeHidden(['flow_metadata']) : null;
        });
        return view('sportvatar')->with(['templatesScript' => static::templatesScriptUrl('all'), 'series' => $series, 'address' => $address, 'sportvatarId' => $sportvatarId, 'sportvatarSales' => $sales, 'sportvatarSaleOffer' => $saleOffer]);
    }
}
