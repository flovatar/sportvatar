<?php

namespace App\Http\Controllers;

use App\Jobs\ExportImage;
use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Sale;
use Illuminate\Http\Request;
use Cache;
use Storage;

class SportvatarImageController extends Controller
{
    public function svg($sportvatarId)
    {
        $sportvatarSvg = Cache::remember('sportvatar-image-'.$sportvatarId, now()->addMinutes(1), function() use ($sportvatarId) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            return ($sportvatar) ? $sportvatar->full_svg : null;
        });
        return $sportvatarSvg ? response($sportvatarSvg)->header('Content-Type', 'image/svg+xml') : abort(404);
    }
    public function svgNoBg($sportvatarId)
    {
        $sportvatarSvg = Cache::remember('sportvatar-image-nobg-'.$sportvatarId, now()->addMinutes(1), function() use ($sportvatarId) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            return ($sportvatar) ? $sportvatar->full_svg_no_bg : null;
        });
        return $sportvatarSvg ? response($sportvatarSvg)->header('Content-Type', 'image/svg+xml') : abort(404);
    }
    public function gif($sportvatarId){
        return Storage::disk('s3')->download('/sportvatar/gif/'.$sportvatarId.'.gif');
    }
    public function gifNoBg($sportvatarId){
        return Storage::disk('s3')->download('/sportvatar/gif/'.$sportvatarId.'-nobg.gif');
    }
    public function png($sportvatarId){
        return Storage::disk('s3')->download('/sportvatar/png/'.$sportvatarId.'.png');
    }
    public function pngNoBg($sportvatarId){
        return Storage::disk('s3')->download('/sportvatar/png/'.$sportvatarId.'-nobg.png');
    }
    public function mp4($sportvatarId){
        return Storage::disk('s3')->download('/sportvatar/mp4/'.$sportvatarId.'.mp4');
    }
    public function mp4NoBg($sportvatarId){
        return Storage::disk('s3')->download('/sportvatar/mp4/'.$sportvatarId.'-nobg.mp4');
    }
}
