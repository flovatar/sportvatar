<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FilteredController;
use Cache;

class SearchController extends FilteredController
{
    public function __invoke()
    {

        $sales = Cache::remember('marketplace-sales-search-'.$this->paramString(), now()->addSeconds(10), function() {
            return $this->getFilteredSales(request());
        });

        return $sales;
    }
}
