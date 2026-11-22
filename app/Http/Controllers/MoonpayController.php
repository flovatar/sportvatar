<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MoonpayController extends Controller
{
    public function sign(Request $request){
        $query = config('sportvatar.moonpayParams').'&apiKey='.config('sportvatar.moonpayPublicKey').'&walletAddress='.$request->address;
        return urlencode(base64_encode(hash_hmac('sha256', $query, config('sportvatar.moonpaySecretKey'), true)));
    }
}
