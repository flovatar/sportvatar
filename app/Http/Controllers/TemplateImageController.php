<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Sale;
use Illuminate\Http\Request;
use Cache;

class TemplateImageController extends Controller
{
    public function __invoke($templateId)
    {
        $templateSvg = Cache::remember('template-image-'.$templateId, now()->addMinutes(60), function() use ($templateId) {
            $template = SportvatarTemplate::where('flow_id', $templateId)->first();
            return ($template) ? $template->full_svg : null;
        });
        return $templateSvg ? response($templateSvg)->header('Content-Type', 'image/svg+xml') : abort(404);
    }
}
