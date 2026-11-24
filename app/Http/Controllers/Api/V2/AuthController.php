<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Wallet login for the headless frontend (web middleware group: session cookie + CSRF).
 * The static site is served by the Worker on its own host, which proxies /api/v2/auth/* to this app on the
 * SAME origin, so the session cookie is first-party (no third-party cookies, no CORS credentials needed).
 * Same verification as /login (FCL account-proof via FCLCrypto, nonce single-use, origin allowlist
 * SPORTVATAR_ACCOUNT_PROOF_ORIGINS must contain the frontend origin). Responses: private, no-store.
 */
class AuthController extends LoginController
{
    public function me()
    {
        $address = $this->authenticatedFlowAddress();

        return response()->json([
            'authenticated' => $address !== null,
            'address' => $address,
            'admin' => Auth::check() && method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function loginJson(Request $request)
    {
        $result = $this->submit($request);

        return response()->json($result + ['address' => $this->authenticatedFlowAddress()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function logoutJson(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['result' => true])->header('Cache-Control', 'private, no-store');
    }
}
