<?php

namespace App\Http\Controllers;

use App\Models\Sportbit;
use App\Models\Sportvatar;
use App\Models\SportvatarSeries;
use App\Models\SportvatarTemplate;
use App\Models\SaleOffer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Cache;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Serve an exported image (gif/png/mp4). Default: redirect to the public CDN
     * (images.sportvatar.com), which needs no bucket credentials on the web server.
     * SPORTVATAR_IMAGE_PROXY=download restores the old behaviour (stream from the s3 disk).
     * $path is the legacy bucket key, e.g. /sportvatar/gif/12.gif.
     */
    protected function imageFromStorage(string $path){
        // testnet: legacy exports on the prod CDN belong to other (mainnet) items with the same id
        if(!config('render.legacy_fallback')){
            abort(404);
        }
        if(config('sportvatar.imageProxy') === 'download'){
            return \Illuminate\Support\Facades\Storage::disk('s3')->download($path);
        }
        $relative = preg_replace('#^/?sportvatar/#', '', $path);
        return redirect()->away(rtrim((string) config('sportvatar.imagesUrl'), '/').'/'.ltrim($relative, '/'), 302)
            ->header('Cache-Control', 'public, max-age=300');
    }


    protected function paramString(){
        return implode('--', request()->all());
    }

    // sportvatar_templates columns without the svg / flow_metadata blobs (~33 MB on mainnet)
    public const TEMPLATE_LEAN_COLUMNS = ['id', 'name', 'description', 'layer', 'rarity', 'sport', 'max_mintable', 'minted', 'sportvatar_series_id', 'flow_id', 'flow_address', 'created_at', 'updated_at'];

    /** Template data sets served as scripts: all | series{flow id} (Builder: config sportvatar.series). */
    public static function isTemplateSet(string $set): bool
    {
        return $set === 'all' || (bool) preg_match('/^series[0-9]{1,3}$/', $set);
    }

    protected static function templateQuery(string $set){
        $query = SportvatarTemplate::query()->orderBy('id', 'asc');
        if(str_starts_with($set, 'series')){
            $seriesDbId = SportvatarSeries::whereFlowId((int) substr($set, 6))->orderBy('id')->value('id');
            $query->where('sportvatar_series_id', $seriesDbId ?? -1);
        }
        return $query;
    }

    /** Version of a template set (changes when templates are added/updated). */
    public static function templatesVersion(string $set){
        return Cache::flexible('tpl-version-v1-'.$set, [60, 600], function() use ($set) {
            $row = static::templateQuery($set)->reorder()
                ->selectRaw('count(*) c, max(id) i, max(updated_at) u')
                ->toBase()->first();
            return substr(md5(json_encode($row)), 0, 12);
        });
    }

    /**
     * JS that sets window.templates (with svg, without flow_metadata) for pages that draw Sportvatars
     * client side (Sportvatar page, collection, Builder). Served by TemplateDataController as a separate,
     * versioned, long-cacheable script instead of being inlined in every page (~1.3 MB gzip per page on mainnet).
     */
    public static function templatesScript(string $set){
        $version = static::templatesVersion($set);
        return Cache::flexible('tpl-script-v1-'.$set.'-'.$version, [3600, 86400], function() use ($set) {
            $templates = static::templateQuery($set)->get()->makeHidden(['flow_metadata'])->toArray();
            return 'window.templates = '.json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT).';';
        });
    }

    public static function templatesScriptUrl(string $set){
        return route('templates.data', ['set' => $set, 'v' => static::templatesVersion($set)], false);
    }

    /** Builder template set (series of SPORTVATAR_SERIES). */
    protected function builderTemplateSet(): string
    {
        return 'series'.(int) config('sportvatar.series', 1);
    }

    protected function getTemplates(){
        return Cache::remember('db-templates', now()->addMinutes(60), function() {
            return SportvatarTemplate::orderBy('id', 'asc')->get();
        });
    }

    // The Series used by the Builder and the Sportvatar/Sportbit pages: SPORTVATAR_SERIES (flow id), default 1 (mainnet).
    protected function getSeries($series = null){
        $series = (int) ($series ?? config('sportvatar.series', 1));
        return Cache::remember('db-sportvatar-series-'.$series, now()->addMinutes(60), function() use ($series) {
            return SportvatarSeries::whereFlowId($series)->orderBy('id', 'asc')->get();
        });
    }


    protected function getFlameSales(){
        $self = $this;
        return Cache::remember('db-flame-sales-builder', now()->addSeconds(10), function() use($self) {
            return [
                'common' => $self->getFlameSaleByRarity('common'),
                'rare' => $self->getFlameSaleByRarity('rare'),
                'epic' => $self->getFlameSaleByRarity('epic'),
                'legendary' => $self->getFlameSaleByRarity('legendary')
            ];
        });
    }
    protected function getFlameSaleByRarity($rarity){
        $templatesRarity = SportvatarTemplate::select('id')->whereLayer(0)->whereRarity($rarity)->get()->flatten();
        return SaleOffer::notBlocked()->whereItemType(Sportbit::class)->whereHasMorph('item', [Sportvatar::class, Sportbit::class], function (Builder $query, $type) use($templatesRarity){
            if($type === Sportbit::class){
                $query->whereIn('sportvatar_template_id', $templatesRarity);
            }
        })->orderBy('price', 'asc')->orderBy('created_at', 'asc')->first();
    }
}
