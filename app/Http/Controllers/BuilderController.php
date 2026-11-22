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

        $templates = $this->getTemplates();
        $series = $this->getSeries();
        $flameSales = $this->getFlameSales();

        return view('builder')->with(['templates' => $templates, 'series' => $series, 'flameSales' => $flameSales]);
    }
}
