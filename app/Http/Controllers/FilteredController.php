<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\SaleOffer;
use App\Models\Sale;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use DB;

class FilteredController extends Controller
{
    public const SPORTVATAR_LIST_COLUMNS = ['id', 'mint', 'name', 'sportvatar_series_id', 'flow_id', 'rarity', 'ability', 'flow_address', 'created_at', 'updated_at'];
    public const SPORTBIT_LIST_COLUMNS = ['id', 'sportvatar_template_id', 'flow_id', 'flow_address', 'flow_metadata', 'mint', 'destroyed', 'packed', 'created_at', 'updated_at'];
    public const OFFER_LIST_COLUMNS = ['id', 'item_id', 'item_type', 'flow_id', 'flow_address', 'price', 'currency', 'is_completed', 'is_dapper', 'listing_id', 'created_at', 'updated_at'];

    /**
     * Lean eager load of list items (API v2): no svg / full_svg, Sportvatar metadata without its svg, Sportbit rarity
     * from a lean template row (the legacy shape serialised the full template incl. svg for every Sportbit).
     */
    protected function leanItemConstraints(): array
    {
        return ['item' => function (MorphTo $morphTo) {
            $morphTo->constrain([
                Sportvatar::class => fn ($q) => $q->select(self::SPORTVATAR_LIST_COLUMNS)->selectRaw("JSON_REMOVE(flow_metadata, '$.svg') as flow_metadata"),
                Sportbit::class => fn ($q) => $q->select(self::SPORTBIT_LIST_COLUMNS)->with(['sportvatarTemplate' => fn ($t) => $t->select(['id', 'name', 'layer', 'rarity', 'flow_id', 'max_mintable'])]),
            ]);
        }];
    }

    /** Hide blobs/relations after a lean load (keeps the legacy keys the Alpine components read). */
    protected function leanRows($rows){
        foreach($rows as $row){
            $item = $row instanceof Sportvatar || $row instanceof Sportbit ? $row : $row->item;
            if($item instanceof Sportvatar){
                $item->setAppends([])->makeHidden(['svg']);
            } elseif($item instanceof Sportbit){
                $item->makeHidden(['sportvatarTemplate']);
            }
        }
        return $rows;
    }

    protected function getFilteredSales(Request $req, $lean = false){
        $salesQuery = SaleOffer::query()->notBlocked();

        if($lean){
            $salesQuery->select(array_map(fn ($c) => 'sale_offers.'.$c, self::OFFER_LIST_COLUMNS))
                ->selectRaw("JSON_REMOVE(sale_offers.flow_metadata, '$.svg') as flow_metadata")
                ->with($this->leanItemConstraints());
            $results = $this->runFilterQuery($req, $salesQuery)->paginate(20);
            $this->leanRows($results->getCollection());
            return $results;
        }

        if(!$req->get('nosvg')){
            $salesQuery->with('item' );
        }

        $results = $this->runFilterQuery($req, $salesQuery)->paginate(20);

        if($req->get('nosvg')) {
            $results->makeHidden(['flow_metadata']);
        }
        return $results;
    }


    protected function getFilteredTransactions(Request $req, $lean = false){
        $salesQuery = Sale::query()->notBlocked();

        if($lean){
            $salesQuery->with($this->leanItemConstraints());
            $results = $this->runFilterQuery($req, $salesQuery)->paginate(50);
            $this->leanRows($results->getCollection());
            return $results;
        }

        if($req->get('nosvg')){
            $salesQuery->with(['item' => function($query) {
                return $query->select(['id', 'flow_id','name', 'flow_address']);
            }]);
        } else {
            $salesQuery->with('item' );
        }

        return $this->runFilterQuery($req, $salesQuery)->paginate(50);
    }



    protected function runFilterQuery(Request $req, $salesQuery){


        if($req->get('templatecat') && $req->get('templatecat') != 'all'){
            if($req->get('templatecat') == 'sportvatar'){
                $salesQuery->whereItemType(Sportvatar::class);
            } else {
                $salesQuery->whereItemType(Sportbit::class);
            }
        }


        if($req->get('rarity') && $req->get('rarity') != 'all'){
            $templatesRarity = SportvatarTemplate::select('id')->whereRarity($req->get('rarity'))->get()->flatten();
            $salesQuery->whereHasMorph('item', [Sportvatar::class, Sportbit::class], function (Builder $query, $type) use($req, $templatesRarity){
                if($type === Sportvatar::class){
                    $query->where('rarity', $req->get('rarity'));
                } else if($type === Sportbit::class){
                    $query->whereIn('sportvatar_template_id', $templatesRarity);
                }
            });
        }




        // Listing currency: DUC (Dapper Wallet, shown as USD) or FLOW (Flow Wallet)
        if(in_array($req->get('currency'), ['DUC', 'FLOW'], true)){
            $salesQuery->where('currency', $req->get('currency'));
        }

        if($req->get('pricemin')){
            $salesQuery->where('price', '>=', intval($req->get('pricemin')));
        }
        if($req->get('pricemax')){
            $salesQuery->where('price', '<=', intval($req->get('pricemax')));
        }



        if($req->get('sportbitcat') && $req->get('sportbitcat') != 'all' && $req->get('templatecat') == 'sportbits'){
            $templates = SportvatarTemplate::select('id')->whereLayer($req->get('sportbitcat'))->get()->flatten();

            $salesQuery->whereHasMorph('item', [Sportbit::class], function (Builder $query, $type) use($templates){
                $query->whereIn('sportvatar_template_id', $templates);
            });
        } else if($req->get('templatecat') && $req->get('templatecat') != 'all' && $req->get('templatecat') != 'sportvatar'){
            if($req->get('templatecat') == 'flames') {
                $templates = SportvatarTemplate::select('id')->whereLayer(0)->get()->flatten();
            } else {
                $templates = SportvatarTemplate::select('id')->where('layer', '!=', 0)->get()->flatten();
            }
            $salesQuery->whereHasMorph('item', [Sportvatar::class, Sportbit::class], function (Builder $query, $type) use($templates){
                if($type === Sportbit::class){
                    $query->whereIn('sportvatar_template_id', $templates);
                }
            });
        }

        if($req->get('templateid') && $req->get('templateid') != 'all'){
            $templates = SportvatarTemplate::select('id')->whereFlowId($req->get('templateid'))->get()->flatten();
            $salesQuery->whereHasMorph('item', [Sportbit::class], function (Builder $query, $type) use($templates){
                $query->whereIn('sportvatar_template_id', $templates);
            });
        }
        if($req->get('sportvatar_id')){
            $salesQuery->whereHasMorph('item', [Sportvatar::class], function (Builder $query, $type) use($req){
                $query->where('flow_id', $req->get('sportvatar_id'));
            });
        }

        if($req->get('address') && $req->get('address') != ''){
            // grouped: a bare orWhere would bypass the notBlocked()/other filters
            $address = $req->get('address');
            $salesQuery->where(function ($q) use ($address) {
                $q->where('flow_buyer_address', $address)->orWhere('flow_seller_address', $address);
            });
        }

        if($req->get('datemin') && $req->get('datemin') != ''){
            $salesQuery->where('created_at', '>=', Carbon::parse($req->get('datemin'))->startOfDay());
        }
        if($req->get('datemax') && $req->get('datemax') != ''){
            $salesQuery->where('created_at', '<=', Carbon::parse($req->get('datemax'))->endOfDay());
        }


        if($req->get('sortby')){
            if($req->get('sortby') == 'datenew'){
                $salesQuery->orderBy('created_at', 'desc');
            } else if($req->get('sortby') == 'dateold'){
                $salesQuery->orderBy('created_at', 'asc');
            } else if($req->get('sortby') == 'pricelow'){
                $salesQuery->orderBy('price', 'asc');
            } else if($req->get('sortby') == 'pricehigh'){
                $salesQuery->orderBy('price', 'desc');
            }
        } else {
            $salesQuery->orderBy('created_at', 'desc');
        }

        return $salesQuery;
    }


    protected function getFilteredSportvatars(Request $req, $lean = false){
        $sportvatarsQuery = Sportvatar::with('saleOffers');
        if($lean){
            $sportvatarsQuery = Sportvatar::with(['saleOffers' => fn ($q) => $q->select(self::OFFER_LIST_COLUMNS)])
                ->select(self::SPORTVATAR_LIST_COLUMNS)->selectRaw("JSON_REMOVE(flow_metadata, '$.svg') as flow_metadata");
        }

        if($req->get('rarity') && $req->get('rarity') != 'all'){
            $sportvatarsQuery->where('rarity', $req->get('rarity'));
        }

        if($req->get('mintmin')){
            $sportvatarsQuery->where('flow_id', '>=', intval($req->get('mintmin')));
        }
        if($req->get('mintmax')){
            $sportvatarsQuery->where('flow_id', '<=', intval($req->get('mintmax')));
        }

        if($req->get('template') && $req->get('template') != 'all' && $req->get('layer') && $req->get('layer') != 'all'){
            $sportvatarsQuery->where('flow_metadata->metadata->layers->'.$req->get('layer'), intval($req->get('template')));
        }

        if($req->get('sortby')){
            if($req->get('sortby') == 'datenew'){
                $sportvatarsQuery->orderBy('flow_id', 'desc');
            } else if($req->get('sortby') == 'dateold'){
                $sportvatarsQuery->orderBy('flow_id', 'asc');
            } else if($req->get('sortby') == 'raritylow'){
                $sportvatarsQuery->orderBy('ability', 'asc')->orderBy('flow_id', 'desc');
            } else if($req->get('sortby') == 'rarityhigh'){
                $sportvatarsQuery->orderBy('ability', 'desc')->orderBy('flow_id', 'asc');
            } else {
                $sportvatarsQuery->orderBy('flow_id', 'desc');
            }
        } else {
            $sportvatarsQuery->orderBy('flow_id', 'desc');
        }


        $sportvatars = $sportvatarsQuery->paginate(20);
        if($lean){
            $this->leanRows($sportvatars->getCollection());
        }

        return $sportvatars;
    }


    protected function getFilteredLeaderboard(Request $req){
        $sportvatarsQuery = Sportvatar::select(['*', 'count(*) as count'])->where('count', '>=', 5)->groupBy('flow_address');

        /*
        if($req->get('rarity') && $req->get('rarity') != 'all'){
            $sportvatarsQuery->where('rarity', $req->get('rarity'));
        }

        if($req->get('mintmin')){
            $sportvatarsQuery->where('flow_id', '>=', intval($req->get('mintmin')));
        }
        if($req->get('mintmax')){
            $sportvatarsQuery->where('flow_id', '<=', intval($req->get('mintmax')));
        }

        if($req->get('template') && $req->get('template') != 'all' && $req->get('layer') && $req->get('layer') != 'all'){
            $sportvatarsQuery->where('flow_metadata->metadata->layers->'.$req->get('layer'), intval($req->get('template')));
        }

        if($req->get('sortby')){
            if($req->get('sortby') == 'datenew'){
                $sportvatarsQuery->orderBy('flow_id', 'desc');
            } else if($req->get('sortby') == 'dateold'){
                $sportvatarsQuery->orderBy('flow_id', 'asc');
            } else if($req->get('sortby') == 'raritylow'){
                $sportvatarsQuery->orderBy('ability', 'asc')->orderBy('flow_id', 'desc');
            } else if($req->get('sortby') == 'rarityhigh'){
                $sportvatarsQuery->orderBy('ability', 'desc')->orderBy('flow_id', 'asc');
            } else {
                $sportvatarsQuery->orderBy('flow_id', 'desc');
            }
        } else {
            $sportvatarsQuery->orderBy('flow_id', 'desc');
        }
*/

        $sportvatars = $sportvatarsQuery->paginate(20);

        return $sportvatars;
    }


    protected function getSalesByTemplateId($templateId, $quantity = 1){

        $salesQuery = SaleOffer::with('item')->notBlocked();
        $salesQuery->whereItemType(Sportbit::class)->where('is_dapper', false);

        $templates = SportvatarTemplate::select('id')->whereFlowId($templateId)->get()->flatten();
        $salesQuery->whereHasMorph('item', [Sportbit::class], function (Builder $query, $type) use($templates){
            $query->whereIn('sportvatar_template_id', $templates);
        });

        $salesQuery->orderBy('price', 'asc')->orderBy('created_at', 'asc');

        if($quantity > 4){
            $quantity = 4;
        }
        return $salesQuery->take($quantity)->get();
    }


}
