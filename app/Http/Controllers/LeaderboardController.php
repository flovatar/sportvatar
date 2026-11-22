<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Http\Request;
use Cache;

class LeaderboardController extends FilteredController
{
    public function __invoke()
    {

        $templates = $this->getTemplates()->makeHidden(['flow_metadata', 'svg']);

        return view('leaderboard')->with(['templates' => $templates]);
    }
}
