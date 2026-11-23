<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pack sales ('packsales') and free-pack claims ('packsales:claims') stay off until the owner has
 * signed the contract updates and pack codes v2 are live (SPORTVATAR_PACK_SALES_ENABLED /
 * SPORTVATAR_PACK_CLAIMS_ENABLED). While off, pages answer with a notice (503) and
 * JSON/POST endpoints with {result:false, disabled:true}.
 */
class EnsurePackSalesEnabled
{
    public function handle(Request $request, Closure $next, string $kind = 'sales')
    {
        $enabled = $kind === 'claims' ? config('sportvatar.packClaimsEnabled') : config('sportvatar.packSalesEnabled');
        if ($enabled) {
            return $next($request);
        }

        // Admin wallets (ADMIN_ADDRESSES) can use the pages while they are off, to test before opening to everyone.
        // Safe: claims/purchases still need the on-chain switch (owner-kit S5) and a valid voucher.
        $user = $request->user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $notice = __((string) config('sportvatar.packSalesNotice'));

        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            return response()->json(['result' => false, 'disabled' => true, 'message' => $notice], 503);
        }

        return response()->view('notice', [
            'title' => __('Packs are paused'),
            'message' => $notice,
        ], 503);
    }
}
