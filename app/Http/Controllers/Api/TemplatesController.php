<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FilteredController;
use App\Models\Sale;
use Cache;

class TemplatesController extends FilteredController
{
    public function __invoke()
    {
        return $this->getTemplates()->makeHidden(['flow_metadata', 'svg']);

    }
}
