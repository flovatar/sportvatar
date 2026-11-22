<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Arr;
use Cache;

class CollectionApiController extends Controller
{
    public function address($address)
    {

        $sportvatars = Cache::remember('db-sportvatars-api-'.$address.'-'.$this->paramString(), now()->addMinutes(5), function() use ($address) {
            return Sportvatar::whereFlowAddress($address)->orderBy('created_at', 'desc')->paginate(10);
        });

        return $sportvatars;
    }


    public function latest()
    {
        $sportvatars = Cache::remember('db-sportvatars-latest-'.$this->paramString(), now()->addMinutes(5), function() {
            return Sportvatar::orderBy('created_at', 'desc')->paginate(10);
        });

        return $sportvatars;
    }
}
