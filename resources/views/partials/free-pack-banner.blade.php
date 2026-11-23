{{-- Free packs waiting for the connected wallet (address-bound vouchers, no code needed). Filled by window.checkPendingPacks();
     green "Claim now" pill when claims are open for this wallet type (SPORTVATAR_PACK_CLAIMS_WALLETS), or for admin wallets
     (verified session) while claims are paused; amber "claims open soon" pill (no button) for wallets not offered the claim yet
     (Dapper until Dapper approves the pack transactions). Not on the claim page itself. --}}
@if(!request()->is('pack/claim*', '*/pack/claim*'))
<div id="free-pack-banner"
     x-data="{ fp: { count: 0, canClaim: false, claimsSoon: false, claimUrl: '/pack/claim' }, dismissed: false }"
     @free-packs-pending.window="fp = $event.detail"
     @logged-out.window="fp = { count: 0, canClaim: false, claimsSoon: false, claimUrl: '/pack/claim' }"
     style="display:none;position:fixed;left:50%;transform:translateX(-50%);bottom:44px;z-index:2147483646;max-width:94vw;background:#12b76a;color:#fff;border-radius:999px;box-shadow:0 6px 24px rgba(0,0,0,.35);padding:10px 14px 10px 20px;font:600 15px/1.4 'Roboto Condensed',Arial,sans-serif;align-items:center;gap:12px;"
     :style="{ display: (fp.count > 0 && fp.canClaim && !dismissed) ? 'flex' : 'none' }">
    <span>&#127873; <span x-text="fp.count == 1 ? {{ \Illuminate\Support\Js::from(__('You have 1 free pack waiting')) }} : {{ \Illuminate\Support\Js::from(__('You have :count free packs waiting')) }}.replace(':count', fp.count)"></span></span>
    <a :href="fp.claimUrl" style="background:#fff;color:#0b7a45;border-radius:999px;padding:6px 16px;text-decoration:none;white-space:nowrap;">{{ __('Claim now') }}</a>
    <button type="button" @click.prevent="dismissed = true" aria-label="{{ __('Close') }}" style="background:transparent;border:0;color:#fff;font-size:20px;line-height:1;cursor:pointer;padding:0 4px;">&times;</button>
</div>
<div id="free-pack-soon-banner"
     x-data="{ fp: { count: 0, claimsSoon: false }, dismissed: false }"
     @free-packs-pending.window="fp = $event.detail"
     @logged-out.window="fp = { count: 0, claimsSoon: false }"
     style="display:none;position:fixed;left:50%;transform:translateX(-50%);bottom:44px;z-index:2147483646;max-width:94vw;background:#f79009;color:#fff;border-radius:24px;box-shadow:0 6px 24px rgba(0,0,0,.35);padding:10px 14px 10px 20px;font:600 15px/1.4 'Roboto Condensed',Arial,sans-serif;align-items:center;gap:12px;"
     :style="{ display: (fp.count > 0 && fp.claimsSoon && !dismissed) ? 'flex' : 'none' }">
    <span>&#127873; <span x-text="fp.count == 1 ? {{ \Illuminate\Support\Js::from(__('You have 1 free pack reserved for you')) }} : {{ \Illuminate\Support\Js::from(__('You have :count free packs reserved for you')) }}.replace(':count', fp.count)"></span> &middot; {{ __('Dapper Wallet claims open soon (waiting for Dapper\'s approval)') }}</span>
    <button type="button" @click.prevent="dismissed = true" aria-label="{{ __('Close') }}" style="background:transparent;border:0;color:#fff;font-size:20px;line-height:1;cursor:pointer;padding:0 4px;">&times;</button>
</div>
@endif
