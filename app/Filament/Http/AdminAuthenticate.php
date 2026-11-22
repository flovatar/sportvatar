<?php

namespace App\Filament\Http;

use Filament\Http\Middleware\Authenticate;

/** Filament auth without a Filament login page: guests go to the wallet sign-in explanation. */
class AdminAuthenticate extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        return route('admin.wallet-login');
    }
}
