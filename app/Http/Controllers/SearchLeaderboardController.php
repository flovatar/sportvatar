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

class SearchLeaderboardController extends FilteredController
{
    public function __invoke()
    {

        $teams = Cache::remember('db-leaderboard-search-'.$this->paramString(), now()->addSeconds(30), function() {
            return $this->getFilteredLeaderboard(request());
        });

        return $teams;
    }
}
