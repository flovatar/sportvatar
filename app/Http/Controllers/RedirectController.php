<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Sale;
use Illuminate\Http\Request;
use Cache;

class RedirectController extends Controller
{
    public function sportvatar($sportvatarId)
    {
        $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
        return ($sportvatar && $sportvatar->flow_address) ? redirect('/sportvatars/'.$sportvatar->flow_id.'/'.$sportvatar->flow_address) : abort(404);
    }

    public function component($componentId)
    {
        $component = Sportbit::where('flow_id', $componentId)->first();
        return ($component && $component->flow_address) ? redirect('/components/'.$component->flow_id.'/'.$component->flow_address) : abort(404);
    }
}
