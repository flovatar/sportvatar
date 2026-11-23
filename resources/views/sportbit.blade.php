<x-guest-layout>
    @push('og')
        @php($ogTemplate = ($templates ?? null) ? $templates->first() : null)
        @if($ogTemplate)
        @include('partials.og-item', ['ogType' => 'template', 'ogId' => $ogTemplate->flow_id, 'ogTitle' => 'Sportbit #'.$componentId.' - '.$ogTemplate->name, 'ogUrl' => url('components/'.$componentId)])
        @endif
    @endpush

    <script>
        @if(isset($templates))
            window.templates = @json($templates);
        @endif
        @if(isset($componentSales))
            window.componentSales = @json($componentSales);
        @endif
        @if(isset($componentSaleOffers))
            window.componentSaleOffers = @json($componentSaleOffers);
        @endif
        @if(isset($componentSaleOffer))
            window.componentSaleOffer = @json($componentSaleOffer);
        @endif
    </script>

    <div
         x-data="componentData('{{$address}}', window.templates, window.componentSaleOffer)"
         x-init="initComponent($dispatch, '{{$address}}', {{$componentId}})"
         @fcomponent-loaded.window="component = $event.detail.component; isLoading = false;"
         @fcomponent-sale-loaded.window="sale = $event.detail.sale; isLoading = false; if($event.detail.sale) {currentPrice = parseFloat($event.detail.sale.price); currentPriceChange = currentPrice;}"
         @fcomponent-sale-created.window="handleOfferCreated()"
         @fcomponent-sale-cancelled.window="handleOfferDeleted()"
         @price-changed.window="changePriceOpen = false; currentPrice = currentPriceChange;"
         @fcomponent-sale-purchased.window="handlePurchaseCompleted()"
         @fcomponent-gift-sent.window="handleGiftSent()"
    >



        <!-- Flobit Details Page -->
        <div class="sportvatar-details-section">
            <div class="container">
                @if(\App\Support\Blocklist::isBlocked($address ?? null) || \App\Support\Blocklist::isFlagged(\App\Models\Sportbit::class, $componentId ?? null))
                    @include('partials.incident-notice')
                @endif
                <div class="media">
                    <div class="img-hold">
                        <div class="img-frame">
                            <div x-bind:class="isFlobit() ? 'builder-component-thumb' : ''" x-html="svg()"></div>
                        </div>
                    </div>

                    <div class="info" x-show="!mint()">
                        <dl x-show="!isLoading">
                            <dt>Not Found!</dt>
                            <dd>It's likely that this Sportbit has changed its owner.</dd>
                        </dl>

                        <dl x-show="isLoading">
                            <dt>Loading</dt>
                            <dd>Please wait a few moments...</dd>
                        </dl>
                    </div>

                    <div class="info" x-show="mint()">
                        <h3 x-text="name()"></h3>
                        <dl>
                            <dt>Mint</dt>
                            <dd x-text="'#'+mint()"></dd>
                            <dt>Category</dt>
                            <dd x-text="category()"></dd>
                            <dt>Rarity</dt>
                            <dd x-text="rarity()"></dd>
                        </dl>


                        <dl x-show="isSale()" class="details-input-form">
                            <dt>Sale price</dt>
                            <dd class="price-input-detail">
                                <span x-text="formatPrice(currentPrice)+ ' ' + offerCurrencyLabel()"></span>
                            </dd>
                        </dl>
                        <dl x-show="!isSale() && isOwned() && showSale" class="details-input-form">
                            <dt>Sale price</dt>
                            <dd>
                                <div class="input-panel">
                                    <div class="input-box">
                                        <div class="input-wrap">
                                            <input x-model.number="currentPrice" type="number" name="price" min="0.1" step="0.1">
                                        </div>
                                        <span class="label" x-text="walletCurrencyLabel()">USD</span>
                                    </div>
                                    <button @click.prevent.stop="createSale" x-bind:disabled="!loggedIn()" type="submit" class="btn btn-icon btn-green"><span class="inner"><i class="icon-check"></i></span></button>
                                </div>
                            </dd>
                        </dl>

                        <dl x-show="!isSale() && isOwned() && showGift" class="details-input-form">
                            <dt>{{ __('Send as a gift to') }}</dt>
                            <dd>
                                <div class="input-panel">
                                    <div class="input-box">
                                        <div class="input-wrap">
                                            <input x-model.trim="currentGiftAddress" type="text" name="gift_address" placeholder="0x..." maxlength="18">
                                        </div>
                                    </div>
                                    <button @click.prevent.stop="sendGift" x-bind:disabled="!loggedIn() || !/^0x[0-9a-fA-F]{16}$/.test(currentGiftAddress)" type="submit" class="btn btn-icon btn-green"><span class="inner"><i class="icon-check"></i></span></button>
                                </div>
                                <span class="note">{{ __('The receiving wallet must be set up for Sportvatar.') }}</span>
                            </dd>
                        </dl>
                        <p x-show="isSale() && !isOwned() && user && !canBuyOffer()" class="note" x-text="'{{ __('Listed in') }} '+offerCurrencyLabel()+': {{ __('connect a') }} '+requiredWalletName(componentSaleOffer)+' {{ __('to buy it.') }}'"></p>
                        <div class="btns-row">
                            <div class="btn-wrp">
                                <a x-show="false" x-bind:href="'/marketplace#templatecat=flobits&templateid='+templateId()" class="btn btn-sm btn-lilac"><span class="inner">View in Marketplace</span></a>
                            </div>
                            <div class="btns-right">
                                <div x-show="!isSale() && isOwned() && !showSale && !showGift" class="btn-wrp">
                                    <button @click.prevent.stop="showGift = true" x-bind:disabled="!loggedIn()" class="btn btn-sm btn-green"><span class="inner">{{ __('Gift') }}</span></button>
                                </div>
                                <div x-show="!isSale() && isOwned() && !showSale" class="btn-wrp">
                                    <button @click.prevent.stop="showSale = true; showGift = false" x-bind:disabled="!loggedIn()" class="btn btn-sm btn-green"><span class="inner">Sell</span></button>
                                </div>
                                <div x-show="isSale() && !isOwned() && !showSale" class="btn-wrp">
                                    <button @click.prevent.stop="loggedIn() ? buySale() : connectWallet()" x-bind:disabled="!canBuyOffer()" class="btn btn-sm btn-green"><span class="inner">Buy Now</span></button>
                                </div>
                                <div x-show="isSale() && isOwned() && !showSale" class="btn-wrp">
                                    <button @click.prevent.stop="cancelSale" x-bind:disabled="!loggedIn()" class="btn btn-sm btn-red"><span class="inner">Cancel Sale</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>





        <!--div x-show="!isLoading" class="market-history-chart" x-data="chartData(window.componentSales, window.componentSaleOffers)" x-init="renderChart()">
            <div class="container">
                <div class="title-row">
                    <h2>Sales History</h2>
                </div>
                <div class="chart">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div -->

        <!-- End of flobit Details Page -->


    </div>
    @include('partials.powered-by')


</x-guest-layout>
