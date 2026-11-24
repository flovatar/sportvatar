<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Redirect;
use Session;


class LoginController extends AuthController
{

    public function login() {
        return view('login');
    }

    public function home() {
        return Auth::check() ? response()->redirectToRoute('home') : response()->redirectToRoute('pack'); //view('login')->withMessage($this->getMessage());
    }

    /**
     * Data needed by the front end to log in: the FCL account-proof nonce
     * (fcl.accountProof.resolver) and the message for signUserMessage.
     */
    public function nonce() {
        $address = $this->authenticatedFlowAddress();
        $this->syncLoginMarker($address !== null);

        return response()
            ->json([
                'nonce' => $this->getAccountProofNonce(),
                'message' => $this->getMessage(),
                // Server-side login state for the front end: pages are edge-cached / static and
                // contain nothing user specific.
                'authenticated' => $address !== null,
                'address' => $address,
                // Lets the page show admin-only previews (e.g. the paused free-pack claim page). Only about the
                // session's own user; every admin endpoint checks this again on the server.
                'admin' => Auth::check() && method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin(),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * CSRF token for the front end (axios interceptor in resources/js/bootstrap.js).
     * Cached pages carry no token; this starts the session.
     */
    public function csrfToken(Request $request) {
        return response()
            ->json(['token' => $request->session()->token()])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Every login must be cryptographically verified against the account keys on chain:
     *  - proofType=account-proof: FCL account-proof (Dapper Wallet, Flow Wallet and other wallets that support it)
     *  - otherwise: FCL user signature of the session message (signUserMessage)
     * The posted address is never trusted on its own.
     */
    public function submit(Request $request) {

        $address = strtolower((string) $request->input('address'));
        if(preg_match('/^[0-9a-f]{16}$/', $address)){
            $address = '0x'.$address;
        }
        $result = false;

        if($request->input('proofType') === 'account-proof'){
            $verified = $this->verifyAccountProof($address, strtolower((string) $request->input('nonce')), $request->input('signatures'));
        } else {
            $signatures = $request->input('signatures');
            if(!$signatures && $request->has('signature')){
                $signatures = [['keyId' => $request->input('keyId'), 'signature' => $request->input('signature')]];
            }
            $verified = $this->verifyUserSignature($address, (string) $request->input('message'), $signatures);
        }

        if($verified){
            $this->loginFlowAddress($request, $address);
            $result = true;
        }

        return ['result' => $result];
    }


    public function logout() {
        Auth::logout();
        Session::flush();
        $this->syncLoginMarker(false);
        return Redirect::to('/')->with('notice', 'You\'ve successfully logged out!');
    }

}
