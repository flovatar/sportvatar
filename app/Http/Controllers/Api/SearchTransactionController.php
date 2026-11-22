<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FilteredController;
use Cache;

class SearchTransactionController extends FilteredController
{
    public function __invoke()
    {

        $sales = Cache::remember('db-transactions-search-'.$this->paramString(), now()->addSeconds(10), function() {
            return $this->getFilteredTransactions(request());
        });

        return $sales;
    }
}
