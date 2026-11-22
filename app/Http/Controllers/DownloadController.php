<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Cache;

class DownloadController extends Controller
{
    public function svg($sportvatarId, $address)
    {
        $sportvatar = Sportvatar::whereFlowAddress($address)->whereFlowId($sportvatarId)->first();
        if(!$sportvatar){
            return abort(404);
        }
        return response()->streamDownload(function () use ($sportvatar){
            echo $sportvatar->full_svg;
        }, 'Sportvatar-'.$sportvatar->name.'.svg');
    }

    public function templateSvg($templateId)
    {
        $fullSvg = Cache::remember('db-template-svg-'.$templateId, now()->addMinutes(60), function() use ($templateId) {
            $template = SportvatarTemplate::whereFlowId($templateId)->first();
            return $template ? $template->full_svg : null;
        });

        if(!$fullSvg){
            return abort(404);
        }
        return $fullSvg;
        /*
        return response()->streamDownload(function () use ($fullSvg, $templateId){
            echo $fullSvg;
        }, 'Sportvatar-Template-'.$templateId.'.svg');
        */
    }

    public function png($sportvatarId, $address)
    {
        $sportvatar = Sportvatar::whereFlowAddress($address)->whereFlowId($sportvatarId)->first();
        if(!$sportvatar){
            return abort(404);
        }
        return response()->streamDownload(function () use ($sportvatar){
            echo $sportvatar->png;
        }, 'Sportvatar-'.$sportvatar->name.'.png');
    }
}
