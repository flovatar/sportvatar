<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Http\Request;
use Cache;

class CollectionController extends Controller
{
    public function __invoke($address)
    {
        return view('collection')->with(['templatesScript' => static::templatesScriptUrl('all'), 'address' => $address, 'addressName' => '']);
    }
}
