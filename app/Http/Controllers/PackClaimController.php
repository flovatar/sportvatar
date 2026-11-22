<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use App\Models\PackVoucher;
use App\Services\PackAuthorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pack codes v2. Exchanges a voucher (code, or an entitlement bound to the logged-in wallet) for an on-chain
 * authorisation {packId, expiry, signature} that only works for that wallet (SportvatarPack.claimFreePack).
 *
 * Requires a VERIFIED wallet session (account-proof login, see PR "Web hotfix: verified wallet login").
 */
class PackClaimController extends Controller
{
    public function authorizeClaim(Request $request, PackAuthorizer $authorizer)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Connect and verify your wallet first'], 401);
        }
        $address = PackAuthorizer::normalizeAddress($user->flow_address ?? $user->email);
        $code = trim((string) $request->input('code', ''));

        return DB::transaction(function () use ($address, $code, $authorizer) {
            $query = PackVoucher::query()->lockForUpdate();
            if ($code !== '') {
                $voucher = $query->where('code_hash', PackAuthorizer::hashCode($code))->first();
                if (!$voucher || $voucher->status === 'revoked' || $voucher->status === 'claimed') {
                    return response()->json(['message' => 'This code is not valid or was already used'], 422);
                }
                if ($voucher->assigned_address && $voucher->assigned_address !== $address) {
                    return response()->json(['message' => 'This code belongs to another wallet'], 403);
                }
                if ($voucher->status === 'reserved' && $voucher->authorization_expiry > time() && $voucher->redeemed_by !== $address) {
                    return response()->json(['message' => 'This code is being redeemed by another wallet'], 409);
                }
            } else {
                // address-bound entitlement (e.g. Flovatar holders): no code needed
                $voucher = $query->where('assigned_address', $address)->whereNull('code_hash')
                    ->whereIn('status', ['issued', 'reserved'])->orderBy('id')->first();
                if (!$voucher) {
                    return response()->json(['message' => 'No free pack is waiting for this wallet'], 404);
                }
            }

            // Re-use the pack of a still-valid reservation for the same wallet, otherwise pick a free one.
            $packId = ($voucher->status === 'reserved' && $voucher->authorization_expiry > time() && $voucher->redeemed_by === $address)
                ? (int) $voucher->pack_flow_id
                : $this->pickFreePack();
            if (!$packId) {
                return response()->json(['message' => 'No free pack available right now, please try again later'], 503);
            }

            $auth = $authorizer->authorize('claim', $packId, $address);
            $voucher->update([
                'status' => 'reserved',
                'pack_flow_id' => $packId,
                'redeemed_by' => $address,
                'authorization_expiry' => $auth['expiry'],
                'authorization_epoch' => $auth['epoch'],
            ]);
            return response()->json($auth);
        });
    }

    /**
     * Authorisation for a paid pack the logged-in buyer has reserved (reservation flow of the web hotfix PR).
     */
    public function authorizePurchase(Request $request, PackAuthorizer $authorizer)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Connect and verify your wallet first'], 401);
        }
        $address = PackAuthorizer::normalizeAddress($user->flow_address ?? $user->email);
        $pack = Pack::whereUserId($user->id)->whereNotNull('flow_id')->where('opened', 0)
            ->whereFlowAddress(config('services.flow.address'))->orderByDesc('assigned_at')->first();
        if (!$pack) {
            return response()->json(['message' => 'No reserved pack'], 404);
        }
        return response()->json($authorizer->authorize('purchase', (int) $pack->flow_id, $address));
    }

    private function pickFreePack(): ?int
    {
        $reserved = PackVoucher::activeReservation()->pluck('pack_flow_id')->filter()->all();
        $pack = Pack::whereIn('name', config('packcodes.free_pack_names'))
            ->whereNotNull('flow_id')
            ->whereFlowAddress(config('services.flow.address'))
            ->where('opened', 0)
            ->whereNotIn('flow_id', $reserved)
            ->inRandomOrder()
            ->first();
        return $pack ? (int) $pack->flow_id : null;
    }
}
