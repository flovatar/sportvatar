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
