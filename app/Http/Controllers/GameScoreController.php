<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameScore;

class GameScoreController extends Controller
{
    public function byGame($gameId){
        $scores = GameScore::whereGame($gameId)->orderBy('score', 'desc')->take(100)->get();
        return $scores;
    }

    public function byAddress($address){
        $scores = GameScore::whereFlowAddress($address)->orderBy('score', 'desc')->take(100)->get();
        return $scores;
    }

    public function store(Request $request){
        if($request->has('score') && $request->has('flow_address') && $request->has('game')){
            $gamescore = GameScore::create([
                'name' => $request->get('name'),
                'flow_address' => $request->get('flow_address'),
                'score' => $request->get('score'),
                'game' => $request->get('game')
            ]);
            return $gamescore;
        }
        return abort(403);
    }
}
