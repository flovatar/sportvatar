<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\Whitelist;
use App\Jobs\ReservePack;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Session;
use Cache;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Str;

class PackController extends AuthController
{
    public function home()
    {
        $signature = Auth::check() ? '' : $this->getMessage();
        return view('pack')->with(['signature' => $signature]);
    }


    public function claim($packcode = '')
    {
        $packcode = Str::limit($packcode,133,'');
        return view('pack-claim')->with(['packcode' => $packcode]);
    }


    public function login()
    {
        $signature = Auth::check() ? '' : $this->getMessage();

        return view('pack-login')->with(['signature' => $signature]);
    }
    public function buy()
    {
        $packCountStandard = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Standard Pack')->count();
        $packCountRare = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Rare Pack')->count();
        $packCountEpic = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Epic Pack')->count();
        $packCountLegendary = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Legendary Pack')->count();

        return view('pack-buy')->with([
            'availableStandard' => $packCountStandard,
            'availableRare' => $packCountRare,
            'availableEpic' => $packCountEpic,
            'availableLegendary' => $packCountLegendary
        ]);
    }

    /**
     * Pack name sold for each rarity on the comparison page.
     */
    protected function packNameForRarity($rarity){
        $names = [
            'common' => 'S1 Standard Pack',
            'rare' => 'S1 Rare Pack',
            'epic' => 'S1 Epic Pack',
            'legendary' => 'S1 Legendary Pack',
        ];
        return $names[$rarity] ?? null;
    }

    /**
     * Minutes a reservation stays valid (sportvatar:clean-packs releases expired ones).
     */
    protected function reservationMinutes(){
        return max(1, (int) config('sportvatar.packReservationMinutes', 60));
    }

    /**
     * Reserves one paid pack of the given rarity for the logged-in wallet.
     * Requires a session created by a verified wallet login (POST /login); the
     * address is never taken from the request. One active reservation per
     * rarity and wallet; reservations expire after packReservationMinutes.
     */
    public function reserve(Request $request, $rarity){

        $address = $this->authenticatedFlowAddress();
        if(!$address){
            return response()->json(['result' => false, 'loginRequired' => true], 401);
        }

        $packName = $this->packNameForRarity($rarity);
        if(!$packName){
            return response()->json(['result' => false], 422);
        }

        $userId = auth()->id();
        $expiredBefore = Carbon::now()->subMinutes($this->reservationMinutes());

        $pack = Pack::readyToSell()->whereName($packName)->where('user_id', $userId)
            ->where('assigned_at', '>', $expiredBefore)->orderBy('assigned_at', 'desc')->first();

        if(!$pack) {
            // Take a free pack, or one whose reservation has expired.
            Pack::readyToSell()->whereName($packName)
                ->where(function($query) use ($expiredBefore){
                    $query->where(function($q){
                        $q->whereNull('user_id')->whereNull('assigned_at');
                    })->orWhere('assigned_at', '<', $expiredBefore);
                })
                ->limit(1)->update([
                    'user_id' => $userId,
                    'assigned_at' => Carbon::now()
                ]);
            $pack = Pack::readyToSell()->whereName($packName)->where('user_id', $userId)
                ->where('assigned_at', '>', $expiredBefore)->orderBy('assigned_at', 'desc')->first();
        }

        Session::put('reserved_pack', $pack ? $pack->flow_id : null);
        Session::put('reserved_rarity', $pack ? $rarity : null);

        return response()->json([
            'result' => $pack ? true : false,
            'soldOut' => $pack ? false : true,
            'redirect' => $pack ? route('pack.purchase', ['rarity' => $rarity]) : null,
        ]);
    }

    public function purchased(Request $request){
        //Auth::logout();
        $request->session()->flush();
        $request->session()->regenerateToken();
        return ['result' => true];
    }

    public function comparison()
    {
        $packsCount = Cache::remember('packs-comparison-count', now()->addMinutes(1), function() {
            $packCountStandard = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Standard Pack')->count();
            $packCountRare = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Rare Pack')->count();
            $packCountEpic = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Epic Pack')->count();
            $packCountLegendary = Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName('S1 Legendary Pack')->count();
            return [
                'availableStandard' => $packCountStandard,
                'availableRare' => $packCountRare,
                'availableEpic' => $packCountEpic,
                'availableLegendary' => $packCountLegendary
            ];
        });

        return view('pack-comparison')->with($packsCount);
    }

    public function purchase($rarity)
    {
        $address = $this->authenticatedFlowAddress();
        $packId = Session::get('reserved_pack', null);

        if(!($address && $packId && Session::get('reserved_rarity') === $rarity)){
            return redirect()->route('pack.comparison');
        }

        $pack = Pack::readyToSell()->where('flow_id', $packId)->where('user_id', auth()->id())
            ->where('assigned_at', '>', Carbon::now()->subMinutes($this->reservationMinutes()))->first();

        if(!$pack){
            return redirect()->route('pack.comparison');
        }

        $price = 9.99;
        $price = ($rarity == 'rare') ? 24.99 : $price;
        $price = ($rarity == 'epic') ? 69.99 : $price;
        $price = ($rarity == 'legendary') ? 199.99 : $price;

        $packDetails = [
            'packId' => $packId,
            'signature' => $pack->signature,
            'price' => $price,
            'address' => $address,
            'processing' => false,
            'rarity' => $rarity
        ];

        return view('pack-purchase')->with($packDetails);
    }

}
