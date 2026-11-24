<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Api\SearchController;
use \App\Http\Controllers\Api\TemplatesController;
use \App\Http\Controllers\Api\SportvatarsController;
use \App\Http\Controllers\Api\ComponentsController;
use \App\Http\Controllers\Api\SearchTransactionController;
use App\Http\Controllers\Api\MarketplaceStatsController;
use App\Http\Controllers\Api\CollectionApiController;
use App\Http\Controllers\Api\GameScoreController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::post('marketplace/search', SearchController::class)->name('api.search.marketplace');
Route::post('transactions/search', SearchTransactionController::class)->name('api.search.transaction');
Route::get('marketplace/stats', MarketplaceStatsController::class)->name('api.stats');
Route::get('templates', TemplatesController::class)->name('api.templates');

Route::get('components/{id}', ComponentsController::class)->whereNumber('id')->name('api.components');

// Free packs waiting for a wallet (address-bound vouchers): count only, no codes (pack codes v2)
Route::get('packs/pending/{address}', \App\Http\Controllers\Api\PendingPackClaimsController::class)
    ->where('address', '0x[a-fA-F0-9]{16}')
    ->middleware('throttle:30,1')
    ->name('api.packs.pending');


Route::get('sportvatars/', [CollectionApiController::class,'latest'])
    ->name('api.sportvatars');

Route::get('sportvatars/{address}', [CollectionApiController::class,'address'])
    ->where('address','0x([a-f0-9]{16})')
    ->name('api.sportvatars.address');

Route::get('sportvatars/{id}', [SportvatarsController::class, 'byId'])->whereNumber('id')->name('api.sportvatars.byId');
Route::get('sportvatars/all', [SportvatarsController::class, 'all'])->name('api.sportvatars.all');


Route::post('sportvatars/search', [SportvatarsController::class,'search'])
    ->name('api.sportvatars.search');



Route::get('leaderboard/game/{gameId}', [GameScoreController::class, 'byGame'])
    ->whereNumber('templateId')
    ->name('api.leaderboard.game');

Route::get('leaderboard/address/{gameId}', [GameScoreController::class, 'byAddress'])
    ->where('address','0x([a-f0-9]{16})')
    ->name('api.leaderboard.address');

Route::post('leaderboard', [GameScoreController::class, 'store'])->name('api.leaderboard.store')->middleware(['auth:sanctum', 'throttle:10,1']);

// Browser render worker (agent box): bearer RENDER_WORKER_TOKEN, stateless, never cached. See RenderJobController.
Route::middleware([\App\Http\Middleware\AuthenticateRenderWorker::class, 'throttle:240,1'])->prefix('render-jobs')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\RenderJobController::class, 'index'])->name('api.renderjobs.index');
    Route::post('claim', [\App\Http\Controllers\Api\RenderJobController::class, 'claim'])->name('api.renderjobs.claim');
    Route::get('{job}/svg/{variant}', [\App\Http\Controllers\Api\RenderJobController::class, 'svg'])
        ->whereNumber('job')->where('variant', 'full|nobg')->name('api.renderjobs.svg');
    Route::post('{job}/complete', [\App\Http\Controllers\Api\RenderJobController::class, 'complete'])->whereNumber('job')->name('api.renderjobs.complete');
    Route::post('{job}/fail', [\App\Http\Controllers\Api\RenderJobController::class, 'fail'])->whereNumber('job')->name('api.renderjobs.fail');
});

/*
| Headless frontend read API v2 (public, GET, edge-cacheable; docs/HEADLESS.md). No api-group IP throttle:
| behind Cloudflare/the Worker the client IP is an edge IP, and the edge cache absorbs repeat traffic.
*/
Route::prefix('v2')
    ->withoutMiddleware('throttle:api')
    ->middleware([\App\Http\Middleware\PublicApiCache::class, 'throttle:6000,1'])
    ->group(function () {
        $p = \App\Http\Controllers\Api\V2\PublicController::class;
        Route::get('meta', [$p, 'meta'])->name('api.v2.meta');
        Route::get('marketplace', [$p, 'marketplace'])->name('api.v2.marketplace');
        Route::get('marketplace/stats', [$p, 'stats'])->name('api.v2.stats');
        Route::get('stats', [$p, 'stats'])->name('api.v2.stats.alias');
        Route::get('transactions', [$p, 'transactions'])->name('api.v2.transactions');
        Route::get('locker-room', [$p, 'lockerRoom'])->name('api.v2.lockerroom');
        Route::get('templates', [$p, 'templates'])->name('api.v2.templates');
        Route::get('templates/{flowId}', [$p, 'template'])->whereNumber('flowId')->name('api.v2.template');
        Route::get('packs', [$p, 'packs'])->name('api.v2.packs');
        Route::get('rankings', [$p, 'rankings'])->name('api.v2.rankings');
        Route::get('{segment}/{id}', [\App\Http\Controllers\Api\V2\ItemController::class, 'showSegment'])
            ->where('segment', 'sportvatars|sportbits|components')->whereNumber('id')->name('api.v2.item');
    });
