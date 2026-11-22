<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Http\Request;
use Cache;

class ShowroomController extends FilteredController
{
    public function __invoke()
    {

        $sportvatars = Cache::remember('db-sportvatars-random-20', now()->addMinutes(1), function() {
            return Sportvatar::inRandomOrder()->take(20)->get();
        });

        $templates = $this->getTemplates()->makeHidden(['flow_metadata', 'svg']);

        return view('showroom')->with(['sportvatars' => $sportvatars, 'templates' => $templates]);
    }
}
