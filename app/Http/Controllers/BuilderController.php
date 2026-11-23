<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Http\Request;
use Cache;

class BuilderController extends Controller
{
    public function __invoke()
    {

        $series = $this->getSeries();
        $templates = $this->getTemplates();
        // Only the templates of the Builder's series (a Sport Flame and its traits must belong to the same series).
        // Mainnet has a single series, so nothing changes there.
        $seriesRow = $series->first();
        if ($seriesRow) {
            $templates = $templates->where('sportvatar_series_id', $seriesRow->id)->values();
        }
        $flameSales = $this->getFlameSales();

        return view('builder')->with(['templates' => $templates, 'series' => $series, 'flameSales' => $flameSales]);
    }
}
