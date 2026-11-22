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

Route::post('leaderboard', [GameScoreController::class, 'store'])->name('api.leaderboard.store');
