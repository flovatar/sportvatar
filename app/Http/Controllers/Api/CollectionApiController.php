<?php

namespace App\Http\Controllers\Api;

use App\Models\Sportvatar;
use Cache;
use App\Http\Controllers\Controller;

class CollectionApiController extends Controller
{
    public function address($address)
    {

        $sportvatars = Cache::remember('db-sportvatars-api-'.$address, now()->addMinutes(5), function() use ($address) {
            return Sportvatar::whereFlowAddress($address)->orderBy('created_at', 'desc')->paginate(100)->makeHidden(['svg', 'id', 'full_svg', 'full_svg_no_bg']);
        });

        return $sportvatars;
    }


    public function latest()
    {
        $sportvatars = Cache::remember('db-sportvatars-latest-'.$this->paramString(), now()->addMinutes(5), function() {
            return Sportvatar::orderBy('created_at', 'desc')->paginate(100)->makeHidden(['svg', 'id', 'full_svg', 'full_svg_no_bg']);
        });

        return $sportvatars;
    }
}
