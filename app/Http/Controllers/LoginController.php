<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\User;
use App\Models\Whitelist;
use Auth;
use Flow;
use Illuminate\Http\Request;
use Redirect;
use Session;
use Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Hash;


class LoginController extends AuthController
{

    public function login() {
        return view('login');
    }

    public function home() {
        return Auth::check() ? response()->redirectToRoute('home') : response()->redirectToRoute('pack'); //view('login')->withMessage($this->getMessage());
    }
    public function submit(Request $request) {
        $address = request()->get('address');
        $message = request()->get('message');
        $signature = request()->get('signature');
        $keyId = request()->get('keyId');

        $result = false;

        if($message == bin2hex($this->getMessage())){
            try {
                $results = Flow::script('./scripts/verify_signature.cdc')->argAddress($address)->argString($message)->argString($signature)->argInt($keyId)->run();
                info(print_r($results,true));

                $resJson = json_decode($results);
                info(print_r($resJson,true));

                if($resJson && isset($resJson->type) && $resJson->type == 'Bool' && isset($resJson->value) && $resJson->value === true){

                    $whitelistCheck = true;


                    if($whitelistCheck){
                        $user = User::firstOrCreate([
                            'email' => $address
                        ],
                            [
                                'password' => Hash::make(Str::random(16)),
                                'name' => $address
                            ]);
                        Auth::login($user);

                        $result = true;
                    }
                }
            } catch (ProcessFailedException $e) {
                info('Failed Verifying signature!!!');
                //info(print_r($e,true));
            }

        }


        return ['result' => $result];
    }


    public function logout() {
        Auth::logout();
        Session::flush();
        return Redirect::to('/')->with('notice', 'You\'ve successfully logged out!');
    }

}
