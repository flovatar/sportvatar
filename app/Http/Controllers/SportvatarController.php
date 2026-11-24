<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Sale;
use Illuminate\Http\Request;
use Cache;
use Illuminate\Support\Facades\DB;

class SportvatarController extends Controller
{
    public function __invoke($sportvatarId, $address)
    {

        $series = $this->getSeries();
        $sales = Cache::remember('sportvatar-sales-'.$sportvatarId, now()->addMinutes(10), function() use ($sportvatarId) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            return $sportvatar ? $sportvatar->sales()->orderBy('created_at', 'asc')->get() : [];
        });
        $saleOffer = Cache::remember('sportvatar-sale-offers-'.$sportvatarId.'-'.$address, now()->addMinutes(1), function() use ($sportvatarId, $address) {
            $sportvatar = Sportvatar::where('flow_id', $sportvatarId)->first();
            // without flow_metadata: it repeats the whole Sportvatar incl. its SVG (~70 KB) and the page only reads price/listing/currency
            $offer = $sportvatar ? $sportvatar->saleOffers()->notBlocked()->whereFlowAddress($address)->where('is_completed', false)->whereNotNull('listing_id')->whereIn('currency', ['DUC', 'FLOW'])->orderBy('created_at', 'desc')->first() : null;
            return $offer ? $offer->makeHidden(['flow_metadata']) : null;
        });
        return view('sportvatar')->with(['templatesScript' => static::templatesScriptUrl('all'), 'series' => $series, 'address' => $address, 'sportvatarId' => $sportvatarId, 'sportvatarSales' => $sales, 'sportvatarSaleOffer' => $saleOffer,
            'sportvatarSnapshot' => static::chainDataSnapshot((int) $sportvatarId)]);
    }

    /**
     * DB copy of the on-chain SportvatarData (same keys as the get-sportvatar script result, svg null) so the page can
     * still render when the chain read fails or times out; replaced by the live chain data whenever that arrives.
     */
    public static function chainDataSnapshot(int $flowId): ?array
    {
        $row = DB::table('sportvatars')->select(['flow_id', 'mint', 'name', 'rarity'])
            ->selectRaw("JSON_REMOVE(flow_metadata, '$.svg') as meta")->where('flow_id', $flowId)->first();
        if (! $row) {
            return null;
        }
        $meta = json_decode((string) $row->meta, true) ?: [];
        $obj = fn ($v) => is_array($v) && $v ? (object) $v : new \stdClass();
        $layers = [];
        foreach (($meta['layers'] ?? []) as $k => $v) {
            $v = is_array($v) ? ($v['value'] ?? null) : $v; // stored as raw Optional {type, value} by older syncs
            if ($v !== null && $v !== '') {
                $layers[(string) $k] = (string) $v;
            }
        }

        return [
            'id' => (string) $flowId,
            'mint' => (string) ($row->mint ?? ($meta['mint'] ?? $flowId)),
            'series' => (string) ($meta['series'] ?? '1'),
            'name' => (string) ($row->name ?? ($meta['name'] ?? '')),
            'rarity' => (string) ($row->rarity ?? ($meta['rarity'] ?? '')),
            'svg' => null,
            'combination' => (string) ($meta['combination'] ?? ''),
            'creatorAddress' => $meta['creatorAddress'] ?? null,
            'layers' => $obj($layers),
            'bio' => $obj($meta['bio'] ?? []),
            'metadata' => $obj($meta['metadata'] ?? []),
            'stats' => $obj($meta['stats'] ?? []),
            'fromSnapshot' => true,
        ];
    }
}
