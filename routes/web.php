<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ShowroomController;
use App\Http\Controllers\BuilderController;
use App\Http\Controllers\BuilderSalesController;
use App\Http\Controllers\PackController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SportvatarController;
use App\Http\Controllers\SportvatarImageController;
use App\Http\Controllers\TemplateImageController;
use App\Http\Controllers\SportbitController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CollectionFindController;
use App\Http\Controllers\CollectionApiController;
use App\Http\Controllers\HomeController;
use \App\Http\Controllers\DownloadController;
use \App\Http\Controllers\TwitterController;
use \App\Http\Controllers\LoginController;
use \App\Http\Controllers\SubscriberController;
use \App\Http\Controllers\MoonpayController;
use \App\Http\Controllers\GraffleController;
use \App\Http\Controllers\SearchController;
use \App\Http\Controllers\SearchTransactionController;
use \App\Http\Controllers\SearchSportvatarController;
use \App\Http\Controllers\SearchLeaderboardController;
use \App\Http\Controllers\RedirectController;
use \App\Http\Controllers\GameScoreController;
use \App\Http\Controllers\MemeController;
use App\Http\Controllers\LeaderboardController;
use \App\Http\Controllers\TreasureHuntingController;
use \App\Http\Controllers\FubController;
use Illuminate\Support\Facades\App;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::localized(function () {
    Route::get('/', HomeController::class)->name('homepage');
    Route::view('how-it-works', 'getstarted')->name('getstarted');
    Route::get('meme-maker', MemeController::class)->name('mememaker');
    Route::get('builder', BuilderController::class)->name('builder');

    // /packs (comparison) stays visible; selling and claiming are behind the relaunch flags
    // (SPORTVATAR_PACK_SALES_ENABLED / SPORTVATAR_PACK_CLAIMS_ENABLED), which answer with a notice.
    Route::get('packs', [PackController::class,'comparison'])->name('pack.comparison');

    Route::middleware('packsales:claims')->group(function () {
        Route::get('pack/claim/{packcode?}', [PackController::class,'claim'])->name('pack.claim');
        // Pack codes v2 (2026-11 reissue): authorisations bound to the verified wallet session
        Route::post('pack/authorize-claim', [\App\Http\Controllers\PackClaimController::class, 'authorizeClaim'])
            ->middleware(['auth', 'throttle:10,1'])->name('pack.authorize-claim');
    });

    Route::middleware('packsales')->group(function () {
        Route::get('pack', [PackController::class,'home'])->name('pack');
        Route::post('pack/authorize-purchase', [\App\Http\Controllers\PackClaimController::class, 'authorizePurchase'])
            ->middleware(['auth', 'throttle:10,1'])->name('pack.authorize-purchase');
        Route::post('pack/purchased', [PackController::class,'purchased'])->name('pack.purchased');
        // Reserving a paid pack needs a session from a verified wallet login (POST /login).
        Route::post('pack/reserve/{rarity}', [PackController::class,'reserve'])
            ->where('rarity','common|rare|epic|legendary')
            ->middleware('throttle:20,1')
            ->name('pack.reserve');
        Route::get('pack/purchase/{rarity}', [PackController::class,'purchase'])
            ->where('rarity','common|rare|epic|legendary')
            ->name('pack.purchase');
    });
});

//Route::post('pack/reserve', [PackController::class,'reserve'])->name('pack.reserve')->middleware(['auth:sanctum', 'verified', 'queueit']);



Route::post('builder/search', BuilderSalesController::class)->name('builder.search');
Route::post('subscribe', [SubscriberController::class,'store'])->name('subscribe')->middleware('throttle:5,1');
Route::post('moonpay/sign', [MoonpayController::class,'sign'])->name('moonpay.sign');
// Legacy Graffle webhook: replaced by the built-in indexer (flow:catchup / flow:stream).
if(config('services.graffle.enabled')){
    Route::post('graffle', GraffleController::class)->name('grafflepost')->middleware('throttle:600,1');
}
Route::get('search', SearchController::class)->name('search');
Route::post('search', SearchController::class)->name('searchpost');
Route::get('marketplace', MarketplaceController::class)->name('marketplace');


Route::get('collection/api/', [CollectionApiController::class,'latest'])
    ->where('address','0x([a-f0-9]{16})')
    ->name('collectionapi');

Route::get('collection/api/{address}', [CollectionApiController::class,'address'])
    ->where('address','0x([a-f0-9]{16})')
    ->name('collectionapiaddress');

Route::get('templates/svg/{templateId}.svg', [DownloadController::class,'templateSvg'])
    ->whereNumber('templateId')
    ->name('templatesvg');


Route::get('api/image/{sportvatarId}', [SportvatarImageController::class,'svg'])
    ->whereNumber('sportvatarId')
    ->name('sportvatarimageapi');
Route::get('api/image/nobg/{sportvatarId}', [SportvatarImageController::class,'svgNoBg'])
    ->whereNumber('sportvatarId')
    ->name('sportvatarimageapinobg');

Route::get('api/image/template/{templateId}', TemplateImageController::class)
    ->whereNumber('templateId')
    ->name('templateimageapi');
// PNG/JPG: static renders (resvg -> R2); legacy CDN export as fallback on prod only
Route::get('api/image/png/{sportvatarId}', [SportvatarImageController::class,'png'])
    ->whereNumber('sportvatarId')
    ->name('sportvatarimageapipng');
Route::get('api/image/jpg/{sportvatarId}', [SportvatarImageController::class,'jpg'])
    ->whereNumber('sportvatarId')
    ->name('sportvatarimageapijpg');
Route::get('api/image/gif/{sportvatarId}', [SportvatarImageController::class,'gif'])
    ->whereNumber('sportvatarId')
    ->name('sportvatarimageapigif');
Route::get('api/image/mp4/{sportvatarId}', [SportvatarImageController::class,'mp4'])
    ->whereNumber('sportvatarId')
    ->name('sportvatarimageapimp4');
// full.png | full.jpg | thumb.jpg | og.jpg
Route::get('api/image/r/{type}/{flowId}/{variant}', \App\Http\Controllers\RenderedImageController::class)
    ->where('type', 'sportvatar|template')
    ->whereNumber('flowId')
    ->where('variant', 'full\\.png|full\\.jpg|thumb\\.jpg|og\\.jpg')
    ->name('renderedimage');





Route::get('locker-room', ShowroomController::class)->name('showroom');
Route::post('locker-room/search', SearchSportvatarController::class)->name('searchsportvatar');

Route::get('leaderboard', LeaderboardController::class)->name('leaderboard');
Route::post('leaderboard/search', SearchLeaderboardController::class)->name('searchleaderboard');


//Route::view('contact', 'contact')->name('contact');
Route::view('privacy', 'privacy')->name('privacy');
Route::view('privacy-it', 'privacy-it')->name('privacy-it');

Route::view('hall-of-fame', 'hall-of-fame')->name('hall-of-fame');
Route::view('mosaic', 'mosaic')->name('mosaic');


Route::get('sportvatars/{sportvatarId}', [RedirectController::class, 'sportvatar']);
Route::get('components/{componentId}',  [RedirectController::class, 'component']);

Route::get('transactions/{address?}', TransactionController::class)
    ->where('address','0x([a-f0-9]{16})')
    ->name('transactions');

Route::post('transactions/search', SearchTransactionController::class)->name('searchtransaction');

Route::get('sportvatars/{sportvatarId}/{address}', SportvatarController::class)
    ->whereNumber('sportvatarId')
    ->where('address','0x([a-f0-9]{16})')
    ->name('sportvatar');

Route::get('sportvatars/{sportvatarId}/{address}/download/svg', [DownloadController::class,'svg'])
    ->whereNumber('sportvatarId')
    ->where('address','0x([a-f0-9]{16})')
    ->name('sportvatar-download-svg');

Route::get('sportvatars/{sportvatarId}/{address}/download/png', [DownloadController::class,'png'])
    ->whereNumber('sportvatarId')
    ->where('address','0x([a-f0-9]{16})')
    ->name('sportvatar-download-png');

Route::get('components/{componentId}/{address}', SportbitController::class)
    ->whereNumber('componentId')
    ->where('address','0x([a-f0-9]{16})')
    ->name('sportvatarcomponent');

Route::get('collection/{address}', CollectionController::class)
    ->where('address','0x([a-f0-9]{16})')
    ->name('collection');

Route::get('collection/{name}', CollectionFindController::class)
    ->name('collectionfind');






Route::get('twitter/login', [TwitterController::class,'login'])->name('twitter.login');
Route::get('twitter/callback', [TwitterController::class,'callback'])->name('twitter.callback');
Route::get('twitter/error', [TwitterController::class,'error'])->name('twitter.error');
Route::get('twitter/logout', [TwitterController::class,'logout'])->name('twitter.logout');
Route::get('twitter/webhook', [TwitterController::class,'webhook'])->name('twitter.webhook');

Route::get('sportvatars/{sportvatarId}/{address}/twitter', [TwitterController::class,'setProfileImage'])
    ->whereNumber('sportvatarId')
    ->where('address','0x([a-f0-9]{16})')
    ->name('twitter.profile');




/* Route::get('flovatest', \App\Http\Controllers\FlovatestController::class)->name('flovatest'); */

// Wallet login: every login is verified on chain (FCL account-proof or user signature, FCLCrypto).
Route::get('/login/nonce', [LoginController::class,'nonce'])->name('login.nonce')->middleware('throttle:60,1');
Route::post('/login', [LoginController::class,'submit'])->name('login.submit')->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class,'logout'])->name('logout');

// Filament admin (/admin) has no password login: sign in with the wallet, then open /admin.
Route::view('admin/login', 'admin-login')->name('admin.wallet-login');
