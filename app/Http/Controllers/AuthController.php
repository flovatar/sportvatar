<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use App\Models\User;
use App\Support\FlowAccountProof;
use Auth;
use Flow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Redirect;
use Session;
use Str;
use Symfony\Component\Process\Exception\ProcessFailedException;


class AuthController extends Controller
{
    // Account-proof nonces are valid for this many seconds.
    const ACCOUNT_PROOF_TTL = 1800;

    protected function getMessage(){
        if(!Session::has('login_hash')){
            Session::put('login_hash', Str::random(32));
        }
        return Session::get('login_hash');
    }

    /**
     * Server-issued nonce (32 random bytes, hex) used by the FCL account-proof
     * service. It is bound to the session and expires after ACCOUNT_PROOF_TTL.
     */
    protected function getAccountProofNonce(){
        $nonce = Session::get('account_proof_nonce');
        $issuedAt = (int) Session::get('account_proof_nonce_at', 0);

        if(!$nonce || (time() - $issuedAt) > self::ACCOUNT_PROOF_TTL){
            $nonce = bin2hex(random_bytes(32));
            Session::put('account_proof_nonce', $nonce);
            Session::put('account_proof_nonce_at', time());
        }

        return $nonce;
    }

    protected function forgetLoginNonces(){
        Session::forget(['login_hash', 'account_proof_nonce', 'account_proof_nonce_at']);
    }

    /**
     * Origins accepted as FCL appIdentifier (FCL sets it to window.location.origin).
     */
    protected function accountProofAppIdentifiers(){
        $configured = config('sportvatar.accountProofOrigins');
        $origins = is_array($configured) ? $configured : explode(',', (string) $configured);

        if(count(array_filter($origins)) == 0){
            $parts = parse_url((string) config('app.url'));
            if($parts && isset($parts['scheme']) && isset($parts['host'])){
                $origins = [$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')];
            }
        }

        return array_values(array_unique(array_filter(array_map(function($origin){
            return rtrim(trim($origin), '/');
        }, $origins))));
    }

    /**
     * Normalises a list of composite signatures ({keyId, signature}) sent by the client.
     * Returns [keyIds, signatures] or null when the payload is malformed.
     */
    protected function normaliseSignatures($signatures){
        if(!is_array($signatures) || count($signatures) < 1 || count($signatures) > 10){
            return null;
        }

        $keyIds = [];
        $sigs = [];
        foreach($signatures as $sig){
            if(!is_array($sig) || !isset($sig['keyId']) || !isset($sig['signature']) || !is_string($sig['signature'])){
                return null;
            }
            $keyId = $sig['keyId'];
            $signature = strtolower($sig['signature']);
            if(!(is_int($keyId) || (is_string($keyId) && ctype_digit($keyId))) || (int) $keyId > 1000){
                return null;
            }
            if(!preg_match('/^[0-9a-f]{128}$/', $signature)){
                return null;
            }
            $keyIds[] = (int) $keyId;
            $sigs[] = $signature;
        }

        return [$keyIds, $sigs];
    }

    protected function verifyAccountProof($address, $nonce, $signatures){
        if(!FlowAccountProof::isValidAddress($address) || !FlowAccountProof::isValidNonce($nonce)){
            return false;
        }

        $expected = Session::get('account_proof_nonce');
        $issuedAt = (int) Session::get('account_proof_nonce_at', 0);
        if(!$expected || !hash_equals($expected, $nonce) || (time() - $issuedAt) > self::ACCOUNT_PROOF_TTL){
            return false;
        }

        $normalised = $this->normaliseSignatures($signatures);
        if(!$normalised){
            return false;
        }

        foreach($this->accountProofAppIdentifiers() as $appIdentifier){
            $message = FlowAccountProof::encodeMessage($appIdentifier, $address, $nonce);
            if($this->runFclCryptoScript('verify_account_proof', $address, $message, $normalised[0], $normalised[1])){
                return true;
            }
        }

        return false;
    }

    protected function verifyUserSignature($address, $message, $signatures){
        if(!FlowAccountProof::isValidAddress($address) || !is_string($message)){
            return false;
        }

        if(!hash_equals(bin2hex($this->getMessage()), $message)){
            return false;
        }

        $normalised = $this->normaliseSignatures($signatures);
        if(!$normalised){
            return false;
        }

        return $this->runFclCryptoScript('verify_user_signature', $address, $message, $normalised[0], $normalised[1]);
    }

    /**
     * Verifies signatures with the FCLCrypto contract (deployed by the Flow team)
     * through the Flow CLI, the same way the app runs its other scripts.
     */
    protected function runFclCryptoScript($script, $address, $message, $keyIds, $signatures){
        $network = config('flow.network');
        if(!in_array($network, ['mainnet', 'testnet'])){
            info('Wallet signature verification is only available on mainnet/testnet (FLOW_NETWORK='.$network.')');
            return false;
        }

        try {
            $results = Flow::script('./scripts/'.$script.'_'.$network.'.cdc')
                ->argAddress($address)
                ->argString($message)
                ->argArrayInt($keyIds)
                ->argArrayString($signatures)
                ->run();

            $resJson = json_decode($results);

            return $resJson && isset($resJson->type) && $resJson->type == 'Bool' && isset($resJson->value) && $resJson->value === true;
        } catch (ProcessFailedException $e) {
            info('Failed verifying wallet signature for '.$address);
        }

        return false;
    }

    protected function loginFlowAddress(Request $request, $address){
        $user = User::firstOrCreate([
            'email' => $address
        ],
            [
                'password' => Hash::make(Str::random(32)),
                'name' => $address
            ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->forgetLoginNonces();

        return $user;
    }

    /**
     * Flow address of the authenticated user (users.email holds the wallet address).
     */
    protected function authenticatedFlowAddress(){
        if(!Auth::check()){
            return null;
        }
        $address = strtolower((string) Auth::user()->email);
        return FlowAccountProof::isValidAddress($address) ? $address : null;
    }

}
