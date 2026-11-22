<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Http\Request;
use Cache;

class CollectionFindController extends Controller
{
    public function __invoke($name)
    {
        $templates = $this->getTemplates();
        return view('collection')->with(['templates' => $templates, 'addressName' => $name, 'address' => '']);
    }
}
