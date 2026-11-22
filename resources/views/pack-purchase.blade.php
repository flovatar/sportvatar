<x-guest-layout>

    <div    x-data="packSaleData('{{ $rarity }}', {{ isset($packId) ? $packId : 0 }}, '{{ isset($signature) ? $signature : '' }}', {{ isset($price) ? $price : 0.0 }}, '{{ isset($address) ? $address : '' }}')"
            @pack-checked.window="packAvailable = $event.detail; if(!packAvailable){ }">


        <section class="purchase-hero-pack" x-bind:class="rarity">
            <div class="container" >
                <div class="pack-img" id="pack-img-hero">
                    <img x-bind:src="'/images/packs/pack-'+rarity+'.svg'" alt="">
                    <div class="pack-price-badge" x-bind:class="'badge-'+rarity">
                        <span class="pack-name" x-text="rarity"></span>
                        <span class="pack-price"><span class="currency">$</span><span x-text="getPackPrice()"></span></span>
                    </div>
                </div>

                <div class="purchase-pack-controls" x-show="!sealingPack" >
                    <button x-show="!loggedIn() && !userCheckLoading" @click.prevent.stop="connectWallet()" class="jshidden btn-o" x-bind:class="'cta-'+rarity">
                        {{ __('Login to Buy') }}
                    </button>
                    <button x-show="loggedIn() && packAvailable && hasCorrectAddress()" @click.prevent.stop="purchasePack()" class="btn-o btn-home-cta jshidden" x-bind:class="'cta-'+rarity">
                        {{ __('Buy Now') }}
                    </button>
                    <a x-show="loggedIn() && !hasCorrectAddress()" href="{{ route('pack.comparison') }}" class="btn-o btn-home-cta">
                        {{ __('Invalid Address') }}
                    </a>
                    <a x-show="!packAvailable" @click.prevent.stop="" href="{{ route('pack.comparison') }}" class="btn-red-error btn-o btn-home-cta">
                        {{ __('Pack already purchased!') }}
                    </a>
                </div>
                <div class="purchase-pack-msg" x-show="!sealingPack" >
                    {{ __('Almost there! You are one step away from your Sportvatar. Approve the Dapper transaction to complete the purchase.') }}
                </div>
            </div>
            <div x-show="sealingPack" class="container pack-footer-btn-note jshidden">
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
                    <span class="note pack-footer-note" x-show="packPurchased">{{ __('Congratulations! You are now ready to reveal the content of your Pack and build your Sportvatars') }}</span>
                </div>
            </div>
        </section>


    </div>

    @include('partials/powered-by')


</x-guest-layout>
