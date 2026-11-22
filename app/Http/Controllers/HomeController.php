<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Cache;

class HomeController extends Controller
{
    public function __invoke()
    {

        $sportvatars = Cache::remember('db-sportvatars-latest-20-no-bg', now()->addMinutes(1), function() {
            $fotw = [
                3980,
                629,
                4927,
                5020,
                4666,
                638,
                4332,
                5518,
                5370,
                741,
                4552,
                927,
                5370,
                5518
            ];
            $sportvatars = Sportvatar::whereIn('flow_id', $fotw)->get()->makeHidden(['svg', 'full_svg', 'flow_metadata']);
            $randSportvatars = Sportvatar::inRandomOrder()->take(10)->get()->makeHidden(['svg', 'full_svg', 'flow_metadata']);
            return $sportvatars->concat($randSportvatars);
        });

        return view('home')->with(['sportvatars' => $sportvatars, 'templates' => []]);
    }
}
