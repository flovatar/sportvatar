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
        // Only the templates of the Builder's series (a Sport Flame and its traits must belong to the same series),
        // served as a versioned script (/templates/data/series{N}.js). Mainnet has a single series.
        $flameSales = $this->getFlameSales();

        return view('builder')->with(['templatesScript' => static::templatesScriptUrl($this->builderTemplateSet()), 'series' => $series, 'flameSales' => $flameSales]);
    }
}
