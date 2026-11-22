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

class SearchSportvatarController extends FilteredController
{
    public function __invoke()
    {

        $sportvatars = Cache::remember('db-sportvatar-search-'.$this->paramString(), now()->addSeconds(30), function() {
            return $this->getFilteredSportvatars(request());
        });

        return $sportvatars;
    }
}
