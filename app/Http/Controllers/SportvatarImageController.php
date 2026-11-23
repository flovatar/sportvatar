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
        return $this->imageFromStorage('/sportvatar/gif/'.$sportvatarId.'.gif');
    }
    public function gifNoBg($sportvatarId){
        return $this->imageFromStorage('/sportvatar/gif/'.$sportvatarId.'-nobg.gif');
    }
    public function png($sportvatarId){
        return RenderedImageController::respond('sportvatar', (int) $sportvatarId, 'full.png');
    }
    public function jpg($sportvatarId){
        return RenderedImageController::respond('sportvatar', (int) $sportvatarId, 'full.jpg');
    }
    public function pngNoBg($sportvatarId){
        return $this->imageFromStorage('/sportvatar/png/'.$sportvatarId.'-nobg.png');
    }
    public function mp4($sportvatarId){
        return $this->imageFromStorage('/sportvatar/mp4/'.$sportvatarId.'.mp4');
    }
    public function mp4NoBg($sportvatarId){
        return $this->imageFromStorage('/sportvatar/mp4/'.$sportvatarId.'-nobg.mp4');
    }
}
