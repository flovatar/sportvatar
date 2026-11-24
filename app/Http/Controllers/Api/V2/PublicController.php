<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilteredController;
use App\Models\Pack;
use App\Models\RenderedImage;
use App\Models\Sale;
use App\Models\SaleOffer;
use App\Models\Sportbit;
use App\Models\Sportvatar;
use App\Models\SportvatarSeries;
use App\Models\SportvatarTemplate;
use App\Services\Headless\ItemSnapshot;
use App\Support\Blocklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public, cacheable read API for the headless frontend (GET only; middleware PublicApiCache sets
 * Cache-Control: public, max-age=0, s-maxage=60, stale-while-revalidate=300 + CORS *).
 *
 * Only cross-wallet questions live here (search/filter/sort, history, stats, rankings); anything a Cadence
 * script can answer for one wallet/item is read in the browser via FCL (chain-first).
 * List endpoints reuse the existing queries (FilteredController, lean variant: no svg blobs) and keep the legacy
 * response shape (Laravel paginator JSON) so the existing Alpine components render them unchanged.
 * Every response is also memoised in Redis for a few seconds (Cache::flexible) to protect the origin when the
 * edge cache is cold or bypassed (workers.dev has no edge cache).
 */
class PublicController extends FilteredController
{
    private function remember(Request $request, string $name, int $fresh, callable $fn)
    {
        $params = $request->query();
        ksort($params);
        $key = 'apiv2:'.$name.':'.md5(json_encode($params));

        return Cache::flexible($key, [$fresh, $fresh * 10], $fn);
    }

    public function meta()
    {
        $builderSet = 'series'.(int) config('sportvatar.series', 1);

        return [
            'api' => 2,
            'network' => config('services.flow.network'),
            'contracts' => ['sportvatar' => config('sportvatar.contractAddress') ?: config('services.flow.address')],
            'frontend_url' => config('headless.frontend_url'),
            'images_url' => config('render.public_url'),
            'series' => (int) config('sportvatar.series', 1),
            'templates' => collect(['all', $builderSet])->unique()->mapWithKeys(fn ($set) => [
                $set => ['version' => Controller::templatesVersion($set), 'script' => Controller::templatesScriptUrl($set)],
            ]),
            'wallets' => config('sportvatar.wallets'),
            'pack_claims' => ['open' => (bool) config('sportvatar.packClaimsEnabled'), 'wallets' => config('sportvatar.packClaimWallets')],
        ];
    }

    /** Marketplace listings (same params as POST /search: templatecat, sportbitcat, templateid, rarity, currency, sortby, pricemin, pricemax, page). */
    public function marketplace(Request $request)
    {
        return $this->withThumbs($this->remember($request, 'marketplace', 10, fn () => $this->getFilteredSales($request, true)), true);
    }

    /** USD (Dapper) sales stats as shown on the marketplace (+ FLOW volume). */
    public function stats()
    {
        return Cache::flexible('apiv2:stats', [60, 600], function () {
            $usd = Sale::notBlocked()->usd()->selectRaw('sum(price) as total, avg(price) as avg, count(price) as count, max(price) as max')->first();
            $flow = Sale::notBlocked()->where('currency', 'FLOW')->selectRaw('sum(price) as total, avg(price) as avg, count(price) as count, max(price) as max')->first();

            return [
                'total' => $usd->total, 'avg' => $usd->avg, 'count' => $usd->count, 'max' => $usd->max,
                'flow' => ['total' => $flow->total, 'avg' => $flow->avg, 'count' => (int) $flow->count, 'max' => $flow->max],
                'listings' => SaleOffer::notBlocked()->where('is_completed', false)->whereNotNull('listing_id')->count(),
                'sportvatars' => Sportvatar::query()->whereNotNull('flow_id')->count(),
            ];
        });
    }

    /** Sales history (same params as POST /transactions/search; + address). */
    public function transactions(Request $request)
    {
        return $this->withThumbs($this->remember($request, 'transactions', 10, fn () => $this->getFilteredTransactions($request, true)), true);
    }

    /** Locker Room (same params as POST /locker-room/search: rarity, sortby, mintmin, mintmax, layer, template, page). */
    public function lockerRoom(Request $request)
    {
        return $this->withThumbs($this->remember($request, 'locker-room', 30, fn () => $this->getFilteredSportvatars($request, true)), false, 'sportvatar');
    }

    /** Lean template lists (no svg): set=all (default) | series{N} | builder (= SPORTVATAR_SERIES). */
    public function templates(Request $request)
    {
        $set = (string) $request->query('set', 'all');
        if ($set === 'builder') {
            $set = 'series'.(int) config('sportvatar.series', 1);
        }
        abort_unless(Controller::isTemplateSet($set), 404);

        return Cache::flexible('apiv2:templates:'.$set, [60, 3600], function () use ($set) {
            $query = SportvatarTemplate::query()->select(Controller::TEMPLATE_LEAN_COLUMNS)->orderBy('id');
            if ($set !== 'all') {
                $seriesDbId = SportvatarSeries::whereFlowId((int) substr($set, 6))->orderBy('id')->value('id');
                $query->where('sportvatar_series_id', $seriesDbId ?? -1);
            }

            return $query->get()->toArray();
        });
    }

    /** Sportbit type (template) data: template, supply, open listings (floor per currency), sales stats. */
    public function template(int $flowId)
    {
        $data = Cache::flexible('apiv2:template:'.$flowId, [60, 600], function () use ($flowId) {
            $tpl = SportvatarTemplate::query()->select(Controller::TEMPLATE_LEAN_COLUMNS)->where('flow_id', $flowId)->first();
            if (! $tpl) {
                return false;
            }
            $sportbitIds = Sportbit::query()->where('sportvatar_template_id', $tpl->id)->select('id');
            $minted = Sportbit::query()->where('sportvatar_template_id', $tpl->id)->count();
            $listings = SaleOffer::query()->notBlocked()->where('item_type', Sportbit::class)
                ->where('is_completed', false)->whereNotNull('listing_id')->whereIn('item_id', $sportbitIds)
                ->selectRaw('count(*) c, min(price) floor, currency')->groupBy('currency')->get();
            $sales = Sale::query()->notBlocked()->where('item_type', Sportbit::class)->whereIn('item_id', $sportbitIds)
                ->selectRaw('count(*) c, avg(price) avg, max(price) max, max(created_at) last_at')->first();

            return [
                'template' => ItemSnapshot::template($tpl),
                'minted' => $minted,
                'listings' => $listings->map(fn ($l) => ['currency' => $l->currency ?: 'DUC', 'count' => (int) $l->c, 'floor' => (float) $l->floor])->values(),
                'sales' => ['count' => (int) $sales->c, 'avg' => $sales->avg !== null ? round((float) $sales->avg, 2) : null, 'max' => $sales->max !== null ? (float) $sales->max : null, 'last_at' => $sales->last_at],
            ];
        });
        abort_unless($data, 404);

        return $data;
    }

    /**
     * Pack supply (read-only; never secrets/signatures/vouchers). A wallet's packs are read on chain; the
     * free packs waiting for a wallet come from /api/packs/pending/{address} (private, no-store).
     */
    public function packs()
    {
        return Cache::flexible('apiv2:packs', [300, 3600], function () {
            return Pack::query()->selectRaw('sportvatar_series_id, name, flame_count, sum(opened = 1) opened, sum(opened = 0) unopened, count(*) total')
                ->whereNotNull('flow_id')->groupBy('sportvatar_series_id', 'name', 'flame_count')->orderBy('sportvatar_series_id')->orderBy('name')->get()
                ->map(fn ($p) => ['series' => $p->sportvatar_series_id !== null ? (int) $p->sportvatar_series_id : null, 'name' => $p->name,
                    'flames' => $p->flame_count !== null ? (int) $p->flame_count : null, 'opened' => (int) $p->opened, 'unopened' => (int) $p->unopened, 'total' => (int) $p->total])
                ->values();
        });
    }

    /** Rankings: top Sportvatar holders, biggest sales, stats. */
    public function rankings()
    {
        return Cache::flexible('apiv2:rankings', [300, 3600], function () {
            $blocked = Blocklist::addresses();
            $holders = Sportvatar::query()->selectRaw("flow_address, count(*) c, sum(rarity = 'legendary') legendary, sum(rarity = 'epic') epic, sum(rarity = 'rare') rare")
                ->whereNotNull('flow_address')->when($blocked, fn ($q) => $q->whereNotIn('flow_address', $blocked))
                ->groupBy('flow_address')->orderByDesc('c')->limit(25)->get()
                ->map(fn ($r) => ['address' => $r->flow_address, 'sportvatars' => (int) $r->c, 'legendary' => (int) $r->legendary, 'epic' => (int) $r->epic, 'rare' => (int) $r->rare]);
            $topSales = Sale::query()->notBlocked()->with($this->leanItemConstraints())
                ->orderByDesc('price')->limit(25)->get(['id', 'item_id', 'item_type', 'price', 'currency', 'is_dapper', 'flow_buyer_address', 'flow_seller_address', 'completed_at', 'created_at'])
                ->map(function ($s) {
                    $type = array_search($s->item_type, ItemSnapshot::TYPES, true) ?: null;

                    return ['type' => $type, 'id' => $s->item?->flow_id, 'price' => (float) $s->price, 'currency' => $s->currency ?: ($s->is_dapper ? 'DUC' : 'FLOW'),
                        'buyer' => $s->flow_buyer_address, 'seller' => $s->flow_seller_address, 'at' => optional($s->completed_at ?? $s->created_at)->toIso8601String()];
                });

            return ['holders' => $holders, 'top_sales' => $topSales, 'stats' => $this->stats()];
        });
    }

    /**
     * Adds `thumb` (best R2 render, immutable URL) to each row so lists can show images from R2 instead of
     * the origin's SVG endpoint. One query per page. $sales: rows are sale offers/sales with ->item.
     */
    private function withThumbs($paginator, bool $sales, ?string $type = null)
    {
        $rows = $paginator->getCollection();
        $wanted = [];
        foreach ($rows as $row) {
            [$t, $id] = $sales ? $this->rowItem($row) : [$type, $row->flow_id];
            if ($t && $id) {
                $wanted[$t][] = (int) $id;
            }
        }
        $thumbs = [];
        foreach ($wanted as $t => $ids) {
            foreach (RenderedImage::query()->where('item_type', $t)->whereIn('flow_id', array_unique($ids))->whereNotNull('rendered_at')->get() as $r) {
                if ($url = $r->url('thumb.jpg')) {
                    $thumbs[$t.':'.$r->flow_id] = $url;
                }
            }
        }
        foreach ($rows as $row) {
            [$t, $id] = $sales ? $this->rowItem($row) : [$type, $row->flow_id];
            $row->setAttribute('thumb', $thumbs[$t.':'.$id] ?? null);
        }

        return $paginator;
    }

    private function rowItem($row): array
    {
        if ($row->item_type === Sportvatar::class) {
            return ['sportvatar', $row->item?->flow_id];
        }
        if ($row->item_type === Sportbit::class && $row->item) {
            static $tplFlowIds = null;
            $tplFlowIds ??= Cache::remember('apiv2:tpl-flow-ids', 300, fn () => SportvatarTemplate::query()->pluck('flow_id', 'id')->all());

            return ['template', $tplFlowIds[$row->item->sportvatar_template_id] ?? null];
        }

        return [null, null];
    }
}
