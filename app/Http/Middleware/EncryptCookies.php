<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        'vipcookie',
        // login marker read by JS (AuthController::LOGIN_MARKER_COOKIE), value '1'
        'sv_auth',
    ];
}
