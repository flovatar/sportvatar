<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use App\Models\User;
use App\Models\Whitelist;
use App\Jobs\ReservePack;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function reserve($rarity, $address){

        if(!Auth::check()){

            $user = User::firstOrCreate([
                'email' => $address
            ],
                [
                    'password' => Hash::make(Str::random(16)),
                    'name' => $address
                ]);
            Auth::login($user);
        }

        $pack = null;
        if($rarity && ($rarity == 'common' || $rarity == 'rare' || $rarity == 'epic' || $rarity == 'legendary')){
            $packName = 'S1 Standard Pack';
            $packName = ($rarity == 'rare') ? 'S1 Rare Pack' : $packName;
            $packName = ($rarity == 'epic') ? 'S1 Epic Pack' : $packName;
            $packName = ($rarity == 'legendary') ? 'S1 Legendary Pack' : $packName;

            $pack = Pack::readyToSell()->whereName($packName)->where('user_id', auth()->id())->first();
            if(!$pack) {

                Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName($packName)->limit(1)->update([
                    'user_id' => auth()->id(),
                    'assigned_at' => Carbon::now()
                ]);
                $pack = Pack::readyToSell()->whereName($packName)->where('user_id', auth()->id())->orderBy('assigned_at', 'desc')->first();
            }
        }

        Session::put('reserved_address', $address);

        if($pack) {
            Session::put('reserved_pack', $pack->flow_id);
        } else {
            Session::put('reserved_pack', null);
        }
        return redirect()->route('pack.purchase', ['rarity' => $rarity]);
    }

    public function reserveOld(Request $request)
    {
        $packType = $request->get('type');
        $pack = null;

        if($packType && ($packType == 'deluxe' || $packType == 'standard' || $packType == 'flobit')){
            $packName = ($packType == 'deluxe') ? 'Genesis Deluxe Pack' : 'Genesis Standard Pack';
            $packName = ($packType == 'flobit') ? 'Flobit Pack' : $packName;

            $pack = Pack::readyToSell()->whereName($packName)->where('user_id', auth()->id())->first();
            if(!$pack || session('vipcookie', '') == 'hdfh55yyerg5g34t34t344'){

                Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName($packName)->limit(1)->update([
                    'user_id' => auth()->id(),
                    'assigned_at' => Carbon::now()
                ]);
                $pack = Pack::readyToSell()->whereName($packName)->where('user_id', auth()->id())->orderBy('assigned_at', 'desc')->first();
            }

        }

        if($pack) {
            return [
                'packId' => $pack->flow_id,
                'signature' => $pack->signature,
                'processing' => false
            ];
        } else {
            return [
                'packId' => null,
                'signature' => null,
                'processing' => false
            ];
        }
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

        $packId = Session::get('reserved_pack', null);
        $address = Session::get('reserved_address', null);

        if(!($packId && $address)){
            return redirect()->route('pack.comparison');
        }

        if(!Auth::check()){

            $user = User::firstOrCreate([
                'email' => $address
            ],
                [
                    'password' => Hash::make(Str::random(16)),
                    'name' => $address
                ]);
            Auth::login($user);
        }



        $pack = Pack::readyToSell()->where('flow_id', $packId)->where('user_id', auth()->id())->first();

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
