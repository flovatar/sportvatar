<x-guest-layout>
    <script>
        window.packClaimCode = '{{ isset($packcode) ? $packcode : '' }}';
    </script>
    <div    x-data="packData()"
            @free-packs-pending.window="fpCount = $event.detail.count"
            @logged-out.window="fpCount = 0"
            @pack-pre-reserved.window="packData = $event.detail"
            @pack-checked.window="packAvailable = $event.detail; if(!packAvailable){ packRedeemCode = '';}">


        @if(isset($packcode) && $packcode)
        <section class="hero-pack-intro">
            <h1>{{ __('Congratulations, you just won a free pack!') }}</h1>
            <strong class="block-title">{{ __('Have fun in the Builder to create your Sportvatar or scroll down to signup and claim your pack now') }}</strong>
            <a href="{{ route('builder') }}" class="btn-green btn-o btn-home-cta flash">
                {{ __('Build your Sportvatar') }}
            </a>
        </section>
        @endif
        <section class="hero-pack free-pack-form">
            <div class="container">
                <div class="pack-img" id="pack-img-hero">
                    <img src="/images/packs/pack-common-opening.svg" alt="">
                </div>
                <div class="features sales-features">
                    <div class="intro">
                        @if(!isset($packcode) || !$packcode)
                            <strong class="block-title" x-show="loggedIn() && !sealingPack && fpCount == 0">{{ __('Enter the Pack redeem code that you received') }}</strong>
                        @else
                            <strong class="block-title" x-show="loggedIn() && !sealingPack">{{ __('You\'re one step away from your free pack!') }}</strong>
                        @endif
                            <strong class="block-title" x-show="sealingPack && !packPurchased">{{ __('Your transaction is being sealed on the blockchain') }}</strong>
                            <strong class="block-title" x-show="sealingPack && packPurchased">{{ __('All done!') }}</strong>
                            <h1 x-show="loggedIn() && !sealingPack">{{ __('Claim your pack and let the fun begin') }}</h1>
                            <h1 x-show="!loggedIn()">{{ __('Please Login or Signup to claim your free Pack')  }}</h1>
                            <h1 x-show="loggedIn() && sealingPack && packPurchased">{{ __('Congratulations! You just claimed your Sportvatar Pack.') }}</h1>
                            {{-- Free packs linked to the connected wallet (address-bound vouchers): no code needed --}}
                            <div id="free-packs-waiting" x-show="loggedIn() && !sealingPack && fpCount > 0"
                                 style="display:none;text-align:center;margin:24px auto 0;max-width:640px;padding:20px 24px;border:2px solid #12b76a;border-radius:16px;">
                                <strong class="block-title" style="display:block;margin-bottom:6px;" x-text="fpCount == 1 ? {{ \Illuminate\Support\Js::from(__('You have 1 free pack waiting')) }} : {{ \Illuminate\Support\Js::from(__('You have :count free packs waiting')) }}.replace(':count', fpCount)"></strong>
                                <span class="note" style="display:block;margin-bottom:16px;">{{ __('It is linked to the connected wallet: no code needed. Claim it, or claim and open it in one step.') }}</span>
                                <div class="btn-hold" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                                    <button id="claim-waiting-pack" @click.prevent.stop="window.dispatch('loading-started'); window.claimWithVoucher('', false)" class="btn-green btn-o btn-home-cta flash">
                                        <span class="inner">{{ __('Claim pack') }}</span>
                                    </button>
                                    <button id="claim-open-waiting-pack" @click.prevent.stop="window.dispatch('loading-started'); window.claimWithVoucher('', true)" class="btn-green btn-o btn-home-cta">
                                        <span class="inner">{{ __('Claim & open') }}</span>
                                    </button>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </section>


        <section class="pre-sale-registration free-pack-form">
            <div class="container" x-show="!sealingPack">
                <div class="form-content" x-show="loggedIn() && {{ isset($packcode) && $packcode ? 'false' : 'true' }}">
                    <div class="form-content-holder">
                        <div class="form-col">
                            <div class="form-wrap">
                                <div x-show="showSubscription" class="form-row">
                                    <div class="input-hold">
                                        <input class="pack-redeem-input" x-model="packRedeemCode" name="packRedeemCode" type="text" placeholder="{{ __('Pack redeem code') }}">
                                        <span class="note">{{ __('Have a redeem code? Enter it here. No code: free packs linked to your wallet are shown above (or leave this empty and press Get Pack).') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="btn-hold">
                    <button x-show="packAvailable && loggedIn()" @click.prevent.stop="claimFreePack()" class="btn-green btn-o btn-home-cta jshidden flash">
                        <span class="inner">{{ __('Get Pack') }}</span>
                    </button>
                    <button x-show="packAvailable && !loggedIn()" @click.prevent.stop="connectWallet()" class="btn-green btn-o btn-home-cta jshidden flash">
                        {{ __('Login/Signup') }}
                    </button>
                    <button x-show="!packAvailable" @click.prevent.stop="" class="btn-red-error btn-o btn-home-cta jshidden">
                        {{ __('Pack already claimed!') }}
                    </button>
                </div>
                <span class="note">{{ __('Inside each pack you will find a Sport Flame to create your Sportvatar in the Builder and a Sportbit accessory that you can equip whenever you want.') }}</span>
            </div>
            <div x-show="sealingPack" class="container jshidden">
                <div>
                    <div class="btn-panel" x-show="!packPurchased">
                        <span class="loader flash">{{ __('Please wait a few moments') }} <img src="/images/loader-green.svg" width="50px" alt=""></span>
                    </div>
                    <div class="btn-panel" x-show="packPurchased">
                        <a x-bind:href="'/collection/'+address()+'#tab=pack'" class="btn-green btn-o btn-home-cta">
                            <span class="inner">{{ __('View your Pack') }}</span>
                        </a>
                    </div>
                    <span class="note pack-footer-note"  x-show="!packPurchased">{{ __('Once completed you can open your Pack in your Collection page to reveal its content. You can then use the Sport Flame in the Builder to create your very own Sportvatar!') }}</span>
                    <span class="note pack-footer-note" x-show="packPurchased">{{ __('Head down to your Collection page to open the Pack and reveal its content.') }}</span>
                </div>
            </div>
        </section>
    </div>

    @include('partials/powered-by')


</x-guest-layout>
