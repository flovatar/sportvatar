<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemeRequest;
use App\Http\Requests\UpdateMemeRequest;
use App\Models\Meme;
use App\Models\MemeTemplate;
use Cache;

class MemeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {

        $memeTemplates = Cache::remember('db-meme-templates'.$this->paramString(), now()->addMinutes(5), function() {
            return MemeTemplate::orderBy('position', 'desc')->orderBy('created_at', 'desc')->get();
        });
        $memes = Cache::remember('db-meme'.$this->paramString(), now()->addMinutes(5), function() {
            return Meme::orderBy('created_at', 'desc')->paginate(100);
        });

        return view('mememaker')->with(['memeTemplates' => $memeTemplates, 'memes' => $memes]);
    }

}
