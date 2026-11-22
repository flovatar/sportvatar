<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use Auth;
use Illuminate\Http\Request;
use Redirect;
use Session;
use Str;


class AuthController extends Controller
{
    protected function getMessage(){
        if(!Session::has('login_hash')){
            Session::put('login_hash', Str::random(32));
        }
        return Session::get('login_hash');
    }

}
