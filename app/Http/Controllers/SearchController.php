<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\SaleOffer;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Cache;

class SearchController extends FilteredController
{
    public function __invoke()
    {

        $sales = Cache::remember('marketplace-sales-search-'.$this->paramString(), now()->addSeconds(10), function() {
            return $this->getFilteredSales(request());
        });

        //$sales = $this->getFilteredSales(request());
        return $sales;
    }
}
