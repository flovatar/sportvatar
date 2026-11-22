<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;

class FlovatestController extends Controller
{
    public function __invoke()
    {

//        $sportvatars = Sportvatar::orderBy('created_at', 'desc')->whereIn('id', [160, 1288, 859, 1069, 94, 577, 350, 1122])->take(20)->get();
        $sportvatars = Sportvatar::orderBy('created_at', 'desc')->skip(0)->take(200)->get();

        return view('flovatest')->with(['sportvatars' => $sportvatars, 'templates' => []]);
    }
}
