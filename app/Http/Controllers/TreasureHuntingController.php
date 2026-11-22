<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemeRequest;
use App\Http\Requests\UpdateMemeRequest;
use App\Models\Meme;
use App\Models\MemeTemplate;
use Cache;
use Illuminate\Http\Request;
use Auth;

class TreasureHuntingController extends AuthController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('treasure-hunting-verified');
    }
    public function login()
    {
        if(Auth::check()){
            return redirect('treasure-hunting');
        }
        $signature = Auth::check() ? '' : $this->getMessage();
        return view('treasure-hunting')->with(['signature' => $signature]);
    }

    public function verify(Request $request)
    {
        if($request->secret == 'FD4L'){
            return redirect('/treasure-hunting')->with('message', 'When Elon Musk says “In Dust we trust”, the next step will be revealed because it must!');
        } else {

            return redirect('/treasure-hunting')->with('error', 'Sorry, wrong code. Please try again');
        }
    }

}
