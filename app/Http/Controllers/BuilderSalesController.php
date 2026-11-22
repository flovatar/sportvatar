<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\SaleOffer;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Cache;

class BuilderSalesController extends FilteredController
{
    public function __invoke()
    {
        $sales = collect([]);

        $templates = request()->get('templates');

        foreach($templates as $template){
            $sales = $sales->concat(Cache::remember('builder-sales-search-'.$template['id'].'-'.$template['quantity'], now()->addSeconds(10), function() use ($template) {
                return $this->getSalesByTemplateId($template['id'], $template['quantity']);
            }));
        }

        return $sales;

    }
}
