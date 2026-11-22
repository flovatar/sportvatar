<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Sale;
use App\Models\SaleOffer;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Cache;
use Illuminate\Support\Facades\DB;

class SportbitController extends Controller
{
    public function __invoke($componentId, $address)
    {

        $templates = Cache::remember('db-templates-component-'.$componentId, now()->addMinutes(60), function() use ($componentId) {
            return SportvatarTemplate::whereHas('sportbits', function (Builder $query) use ($componentId) {
                $query->whereFlowId($componentId);
            })->orderBy('id', 'asc')->get();
        });

        $templateId = $templates ? $templates->first()->id : 0;
/*
        $sales = Cache::remember('sportvatar-component-sales-'.$templateId, now()->addMinutes(60), function() use ($templateId) {
            $components = Sportbit::select('id')->whereSportvatarTemplateId($templateId)->get()->flatten();
            $sales = Sale::notBlocked()->select(
                [
                    DB::raw('DATE_FORMAT(created_at, "%c/%d") as date'),
                    DB::raw('MAX(created_at) as maxdate'),
                    DB::raw('SUM(price) as total'),
                    DB::raw('COUNT(price) as count'),
                    DB::raw('AVG(price) as avg'),
                    DB::raw('MIN(price) as min'),
                    DB::raw('MAX(price) as max')
                ])
                ->whereIn('item_id', $components)
                ->where('item_type', 'App\Models\Sportbit')
                ->groupBy('date')
                ->orderBy('maxdate')
                ->get();

                return [
                    'date' => $sales->pluck('date'),
                    'total' => $sales->pluck('total'),
                    'count' => $sales->pluck('count'),
                    'avg' => $sales->pluck('avg'),
                    'min' => $sales->pluck('min'),
                    'max' => $sales->pluck('max')
                ];
        });
        $saleoffers = Cache::remember('sportvatar-component-saleoffers-'.$templateId, now()->addMinutes(60), function() use ($templateId) {
            $components = Sportbit::select('id')->whereSportvatarTemplateId($templateId)->get()->flatten();
            $saleoffers = SaleOffer::notBlocked()->select(
                [
                    DB::raw('DATE_FORMAT(created_at, "%c/%d") as date'),
                    DB::raw('MAX(created_at) as maxdate'),
                    DB::raw('SUM(price) as total'),
                    DB::raw('COUNT(price) as count'),
                    DB::raw('AVG(price) as avg'),
                    DB::raw('MIN(price) as min'),
                    DB::raw('MAX(price) as max')
                ])
                ->whereIn('item_id', $components)
                ->where('item_type', 'App\Models\Sportbit')
                ->groupBy('date')
                ->orderBy('maxdate')
                ->get();

            return [
                'date' => $saleoffers->pluck('date'),
                'total' => $saleoffers->pluck('total'),
                'count' => $saleoffers->pluck('count'),
                'avg' => $saleoffers->pluck('avg'),
                'min' => $saleoffers->pluck('min'),
                'max' => $saleoffers->pluck('max')
            ];
        });
*/
        $sales = [];
        $saleoffers = [];

        $saleOffer = Cache::remember('sportvatar-component-sale-offer-'.$componentId.'-'.$address, now()->addMinutes(1), function() use ($componentId, $address) {
            $component = Sportbit::where('flow_id', $componentId)->first();
            return $component ? $component->saleOffers()->notBlocked()->whereFlowAddress($address)->where('is_completed', false)->where('is_dapper', true)->orderBy('created_at', 'desc')->first() : null;
        });
        $series = $this->getSeries();

        return view('sportbit')->with(['templates' => $templates, 'series' => $series, 'address' => $address, 'componentId' => $componentId, 'componentSales' => $sales, 'componentSaleOffers' => $saleoffers, 'componentSaleOffer' => $saleOffer]);
    }
}
