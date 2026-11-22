<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FilteredController;
use App\Models\Sportbit;
use Cache;

class ComponentsController extends FilteredController
{
    public function __invoke($id)
    {
        $component = Sportbit::whereFlowId($id)->first();
        return $component ?  : abort(404);

    }
}
