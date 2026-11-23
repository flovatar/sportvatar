<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PackVoucher;

/**
 * GET /api/packs/pending/{address}: how many free packs are waiting for a wallet (address-bound vouchers, no code
 * needed), so the site can tell a connected wallet "You have N free pack(s) waiting". Sessionless (api group).
 *
 * Returns only a count, whether claims are open and for which wallets: never codes, hashes, batches or pack ids. Claiming still needs
 * the verified wallet session (POST /pack/authorize-claim), which is admin-only while claims are paused.
 */
class PendingPackClaimsController extends Controller
{
    public function __invoke(string $address)
    {
        $address = strtolower($address);
        $count = PackVoucher::where('assigned_address', $address)
            ->whereNull('code_hash')
            ->whereIn('status', ['issued', 'reserved'])
            ->count();

        return response()
            ->json([
                'count' => $count,
                'claimsOpen' => (bool) config('sportvatar.packClaimsEnabled'),
                // wallets the site offers the claim to (SPORTVATAR_PACK_CLAIMS_WALLETS), e.g. ['flow'] until Dapper approves
                'claimWallets' => config('sportvatar.packClaimWallets'),
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
