<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Http\Request;
use Cache;

class CollectionFindController extends Controller
{
    public function __invoke($name)
    {
        return view('collection')->with(['templatesScript' => static::templatesScriptUrl('all'), 'addressName' => $name, 'address' => '']);
    }
}
