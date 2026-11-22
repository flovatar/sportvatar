<x-guest-layout>
    <div class="decor-bg-wrap">
        <section class="container">
            <div class="comparison-table">
             
                    <div class="pack-wrapper">
                        <div class="pack-details common">
                            <div class="pack-image">
                                <img src="/images/packs/pack-common.svg" alt="" />
                            </div>
                            <div class="pack-name">
                                Common
                            </div>
                            <div class="pack-price alt">
                                <span class="currency">$</span>9.99
                            </div>
                            <div class="pack-data">
                                x1 Random Sport Flame
                            </div>
                            <div class="pack-data alt">
                                x1 Random Sportbit or Number
                            </div>
                            <div class="pack-data">
                                &nbsp;
                            </div>
                            <div class="pack-data alt">
                                &nbsp;
                            </div>
                            <div class="pack-data">
                                5,000 Total
                            </div>
                            <div class="pack-buy alt">
                                @if(config('sportvatar.packLive'))
                                    <button x-show="!loggedIn() && !userCheckLoading" @click.prevent.stop="connectWallet()" class="jshidden btn-o btn-white">
                                        {{ __('Login to Buy') }}
                                    </button>
                                    <button x-show="loggedIn()" @click.prevent.stop.debounce="reservePackAndGo('{{ route('pack.reserve', ['rarity' => 'common']) }}')" class="jshidden btn-o btn-white">{{ __('Buy Now') }}</button>
                                @else
                                    <button class="jshidden btn-o btn-white">
                                        {{ __('Coming soon') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pack-wrapper">
                        <div class="pack-details rare">
                            <div class="pack-image">
                                <img src="/images/packs/pack-rare.svg" alt="" />
                            </div>
                            <div class="pack-name">
                                Rare
                            </div>
                            <div class="pack-price alt">
                                <span class="currency">$</span>24.99
                            </div>
                            <div class="pack-data">
                                x1 Rare Sport Flame
                            </div>
                            <div class="pack-data alt">
                                x1 Random Sport Flame
                            </div>
                            <div class="pack-data">
                                x2 Random Sportbits
                            </div>
                            <div class="pack-data alt">
                                x1 Number Modifier
                            </div>
                            <div class="pack-data">
                                2,000 Total
                            </div>
                            <div class="pack-buy alt">
                                @if(config('sportvatar.packLive'))
                                    <button x-show="!loggedIn() && !userCheckLoading" @click.prevent.stop="connectWallet()" class="jshidden btn-o btn-blue">
                                        {{ __('Login to Buy') }}
                                    </button>
                                    <button x-show="loggedIn()" @click.prevent.stop.debounce="reservePackAndGo('{{ route('pack.reserve', ['rarity' => 'rare']) }}')" class="jshidden btn-o btn-blue">{{ __('Buy Now') }}</button>
                                @else
                                    <button class="jshidden btn-o btn-blue">
                                        {{ __('Coming soon') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pack-wrapper">
                        <div class="pack-details epic">
                            <div class="pack-image">
                                <img src="/images/packs/pack-epic.svg" alt="" />
                            </div>
                            <div class="pack-name">
                                Epic
                            </div>
                            <div class="pack-price alt">
                                <span class="currency">$</span>69.99
                            </div>
                            <div class="pack-data">
                                x1 Epic Sport Flame
                            </div>
                            <div class="pack-data alt">
                                x2 Random Sport Flames
                            </div>
                            <div class="pack-data">
                                x4 Random Sportbits
                            </div>
                            <div class="pack-data alt">
                                x2 Number Modifier
                            </div>
                            <div class="pack-data">
                                1,000 Total
                            </div>
                            <div class="pack-buy alt">
                                @if(config('sportvatar.packLive'))
                                    <button x-show="!loggedIn() && !userCheckLoading" @click.prevent.stop="connectWallet()" class="jshidden btn-o btn-yellow">
                                        {{ __('Login to Buy') }}
                                    </button>
                                    <button x-show="loggedIn()" @click.prevent.stop.debounce="reservePackAndGo('{{ route('pack.reserve', ['rarity' => 'epic']) }}')" class="jshidden btn-o btn-yellow">{{ __('Buy Now') }}</button>
                                @else
                                    <button class="jshidden btn-o btn-yellow">
                                        {{ __('Coming soon') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pack-wrapper">
                        <div class="pack-details legendary">
                            <div class="pack-image">
                                <img src="/images/packs/pack-legendary.svg" alt="" />
                            </div>
                            <div class="pack-name">
                                Legendary
                            </div>
                            <div class="pack-price alt">
                                <span class="currency">$</span>199.99
                            </div>
                            <div class="pack-data">
                                x1 Legendary Sport Flame
                            </div>
                            <div class="pack-data alt">
                                x3 Random Sport Flames
                            </div>
                            <div class="pack-data">
                                x6 Random Sportbits
                            </div>
                            <div class="pack-data alt">
                                x4 Number Modifier
                            </div>
                            <div class="pack-data">
                                500 Total
                            </div>
                            <div class="pack-buy alt">
                                @if(config('sportvatar.packLive'))
                                    <button x-show="!loggedIn() && !userCheckLoading" @click.prevent.stop="connectWallet()" class="jshidden btn-o btn-pink">
                                        {{ __('Login to Buy') }}
                                    </button>
                                    <button x-show="loggedIn()" @click.prevent.stop.debounce="reservePackAndGo('{{ route('pack.reserve', ['rarity' => 'legendary']) }}')" class="jshidden btn-o btn-pink">{{ __('Buy Now') }}</button>
                                @else
                                    <button class="jshidden btn-o btn-pink">
                                        {{ __('Coming soon') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

            </div>
        </section>
    </div>

    @include('partials/powered-by')

</x-guest-layout>
