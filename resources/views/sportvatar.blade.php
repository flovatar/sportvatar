<x-guest-layout>
    @push('og')
        @php($ogName = \App\Models\Sportvatar::where('flow_id', $sportvatarId)->value('name'))
        @include('partials.og-item', ['ogType' => 'sportvatar', 'ogId' => $sportvatarId, 'ogTitle' => 'Sportvatar #'.$sportvatarId.($ogName ? ' - '.$ogName : ''), 'ogUrl' => url('sportvatars/'.$sportvatarId)])
    @endpush

    <script>
        @if(isset($templates))
            window.templates = @json($templates);
        @endif
        @if(isset($series))
            window.series = @json($series);
        @endif
        @if(isset($sportvatarSales))
            window.sportvatarSales = @json($sportvatarSales);
        @endif
        @if(isset($sportvatarSaleOffer))
            window.sportvatarSaleOffer = @json($sportvatarSaleOffer);
        @endif

        window.revealStats = {{ request()->get('reveal') ? 'true' : 'false' }};
    </script>
    <div
        x-data="sportvatarData('{{$address}}', {{$sportvatarId}}, window.series, window.templates, window.sportvatarSales, window.sportvatarSaleOffer, window.revealStats)"
        x-init="initSportvatar($dispatch, '{{$address}}', {{$sportvatarId}})"
        @sportvatar-loaded.window="loadSportvatar($event.detail.sportvatar)"
        @sportvatar-sale-loaded.window="loadSale($event.detail.sale)"
        @sportvatar-sale-created.window="handleOfferCreated()"
        @sportvatar-sale-cancelled.window="handleOfferDeleted()"
        @sportvatar-sale-purchased.window="handlePurchaseCompleted()"
        @sportvatar-gift-sent.window="handleGiftSent()"
        @user-loaded.window="getComponents(window.user.addr);"
        @fcomponents-loaded.window="components = $event.detail.components"
        @address-profile-loaded.window="loadCreatorName($event.detail.profile)"
        @address-profile-owner-loaded.window="loadOwnerName($event.detail.profile)"
    >


        <!-- Sportvatar Details Page -->
        <div class="sportvatar-details-section">
            <div class="container">
                @if(\App\Support\Blocklist::isBlocked($address ?? null) || \App\Support\Blocklist::isFlagged(\App\Models\Sportvatar::class, $sportvatarId ?? null))
                    @include('partials.incident-notice')
                @endif
                <div class="media">
                    <div class="img-hold">
                        <div class="img-frame">
                            <div x-html="svg()"></div>
                            <div class="rarity-badge" x-bind:class="getRarityClass()">
                                <div class="inner">
                                    <span class="score" x-text="getOverallScore()" x-bind:class="revealStats ? 'hide-score' : ''"></span>
                                    <span class="rarity" x-bind:class="getRarityClass()" x-text="getRarityClass()"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info" x-show="!mint()">
                        <dl x-show="!isLoading">
                            <dt>Not Found!</dt>
                            <dd>It's likely that this Sportvatar has changed its owner.</dd>
                        </dl>

                        <dl x-show="isLoading">
                            <dt>Loading</dt>
                            <dd>Please wait a few moments...</dd>
                        </dl>
                    </div>

                    <div class="info" x-show="mint()">

                        <!-- Customize Flobits -->
                        <div class="customize-flobits-mobile" x-show="isOwned() && !isSale() && (hasSportbits() || hasSportbitsEquipped())">
                            <div class="container">
                                <div class="title-row">
                                    <h2>{{ __('Customize Sportbits') }}</h2>
                                </div>
                                <div class="customize-flobit-panel">
                                    <ul class="option-list">
                                        <template x-for="layer in sportbitLayers()" :key="layer.id">
                                            <li x-show="(filterComp(layer.id) && filterComp(layer.id).length > 0) || hasSportbit(layer.id)">
                                                <h3 x-text="layer.name"></h3>
                                                <div class="select-wrp">
                                                    <select x-model="optionalIds[layer.id]">
                                                        <option value="" x-text="hasSportbit(layer.id) ? 'Equipped Sportbit' : 'No Sportbit'"></option>
                                                        <template x-for="comp in filterComp(layer.id)" :key="comp.id">
                                                            <option x-bind:value="comp.id" x-text="comp.name + ' #' + comp.mint"></option>
                                                        </template>
                                                    </select>
                                                    <button x-show="optionalIds[layer.id]" @click.prevent.stop="setOptional(layer.id)" x-bind:disabled="!loggedIn() || !optionalIds[layer.id]"  type="submit" class="btn btn-icon btn-green"><span class="inner"><i class="icon-check"></i></span></button>
                                                    <button x-show="!optionalIds[layer.id] && hasSportbit(layer.id)" @click.prevent.stop="removeOptional(layer.id)" x-bind:disabled="!loggedIn() || !hasSportbit(layer.id)" type="submit" class="btn btn-icon btn-red"><span class="inner"><i class="icon-cross02"></i></span></button>
                                                </div>
                                            </li>
                                        </template>

                                    </ul>
                                </div>

                            </div>
                        </div>
                        <!-- end Customize Flobits -->

                        <dl>
                            <dt>Mint</dt>
                            <dd x-text="'#'+mint()"></dd>
                            <dt>Combination</dt>
                            <dd x-text="combination()" class="long-content"></dd>
                            <dt>Owner address</dt>
                            <dd class="long-content">
                                <a x-bind:href="'/collection/'+ownerAddress()" x-text="ownerAddressName()"></a>
                            </dd>
                            <dt>Creator address</dt>
                            <dd class="long-content">
                                <a x-bind:href="'/collection/'+creatorAddress()" x-text="creatorAddressName()"></a>
                            </dd>
                        </dl>

                        <dl class="stats-info" x-bind:class="revealStats ? 'hide-stats' : ''">
                            <template x-for="(stat, index)  in sportvatarStats()">
                                <div class="stats-line">
                                    <dt x-text="index" class="stat-title"></dt>
                                    <dd x-bind:class="'stats-rate-'+stat">
                                        <div class="stats-bar"></div>
                                        <div x-text="window.formatSingleDecimal(parseInt(stat,10)*1.0/2)"></div>
                                    </dd>
                                </div>
                            </template>
                            <button x-show="revealStats" class="stats-reveal btn btn-sm btn-green" @click.prevent.stop="revealStats = false"><span class="inner">{{ __('Reveal Abilities') }}</span></button>
                        </dl>


                        <template x-if="isOwned()">
                            <dl class="details-input-form details-download-btns">
                                <dt>Assets Download</dt>
                                <dd>
                                    <div class="input-panel">
                                        <button @click.prevent.stop="saveSvg()" class="btn btn-sm btn-green"><span class="inner">SVG</span></button>
                                        @include('partials.render-downloads', ['dlType' => 'sportvatar', 'dlId' => $sportvatarId, 'dlName' => 'Sportvatar-'.$sportvatarId, 'dlClass' => 'btn btn-sm btn-green'])
                                    </div>
                                </dd>
                            </dl>
                        </template>
                        <dl x-show="isSale()" class="details-input-form">
                            <dt>Sale price</dt>
                            <dd class="price-input-detail">
                                <div class="input-panel" x-show="isOwned() && !isDapperSale()">
                                    <div class="input-box">
                                        <div class="input-wrap">
                                            <input x-model.number="currentPriceChange" type="number" name="price" min="0.1" step="0.1">
                                        </div>
                                        <span class="label" x-text="offerCurrencyLabel()">USD</span>
                                    </div>
                                    <button @click.prevent.stop="cancelSale" x-bind:disabled="!loggedIn()" type="submit" class="btn btn-icon btn-red"><span class="inner"><i class="icon-cross02"></i></span></button>
                                </div>
                                <span x-show="!isOwned() || isDapperSale()" x-text="formatPrice(currentPrice)+ ' ' + offerCurrencyLabel()"></span>
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
                        <p x-show="isSale() && !isOwned() && user && !canBuyOffer()" class="note" x-text="'{{ __('Listed in') }} '+offerCurrencyLabel()+': {{ __('connect a') }} '+requiredWalletName(sportvatarSaleOffer)+' {{ __('to buy it.') }}'"></p>
                        <div class="btns-row">
                            <div class="btn-wrp">
                                <a x-bind:href="combinationUrl()" class="btn btn-sm btn-green"><span class="inner">View in Builder</span></a>
                            </div>
                            <div class="btns-right">
                                <div x-show="!isSale() && isOwned() && !showSale && !showGift" class="btn-wrp">
                                    <button @click.prevent.stop="showGift = true" x-bind:disabled="!loggedIn()" class="btn btn-sm btn-green"><span class="inner">{{ __('Gift') }}</span></button>
                                </div>
                                <div x-show="!isSale() && isOwned() && !showSale" class="btn-wrp">
                                    <button @click.prevent.stop="showSale = true; showGift = false" x-bind:disabled="!loggedIn()" class="btn btn-sm btn-green"><span class="inner">Sell</span></button>
                                </div>
                                <div x-show="showSaleBtn()" class="btn-wrp">
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



        <!-- Customize Flobits -->
        <div class="customize-flobits" x-show="isOwned() && !isSale() && (hasSportbits() || hasSportbitsEquipped())">
            <div class="container">
                <div class="title-row">
                    <h2>{{ __('Customize Sportbits') }}</h2>
                </div>
                <div class="customize-flobit-panel">
                    <ul class="option-list">
                        <template x-for="layer in sportbitLayers()" :key="layer.id">
                            <li x-show="(filterComp(layer.id) && filterComp(layer.id).length > 0) || hasSportbit(layer.id)">
                                <h3 x-text="layer.name"></h3>
                                <div class="select-wrp">
                                    <select x-model="optionalIds[layer.id]">
                                        <option value="" x-text="hasSportbit(layer.id) ? 'Equipped Sportbit' : 'No Sportbit'"></option>
                                        <template x-for="comp in filterComp(layer.id)" :key="comp.id">
                                            <option x-bind:value="comp.id" x-text="comp.name + ' #' + comp.mint"></option>
                                        </template>
                                    </select>
                                    <button x-show="optionalIds[layer.id]" @click.prevent.stop="setOptional(layer.id)" x-bind:disabled="!loggedIn() || !optionalIds[layer.id]"  type="submit" class="btn btn-icon btn-green"><span class="inner"><i class="icon-check"></i></span></button>
                                    <button x-show="!optionalIds[layer.id] && hasSportbit(layer.id)" @click.prevent.stop="removeOptional(layer.id)" x-bind:disabled="!loggedIn() || !hasSportbit(layer.id)" type="submit" class="btn btn-icon btn-red"><span class="inner"><i class="icon-cross02"></i></span></button>
                                </div>
                            </li>
                        </template>

                    </ul>
                </div>

            </div>
        </div>
        <!-- end Customize Flobits -->

        <div class="sportvatar-traits-wrap straight-bottom">
            <div class="sportvatar-traits">
                <div class="container">
                    <div class="title-row">
                        <h2>Sportvatar Traits</h2>
                    </div>

                    <div class="thumb-hold">
                        <template x-for="template in getSportvatarBasicTemplates()" :key="template.id">
                            <div class="thumb" x-bind:class="template.rarity">
                                <div class="img-hold" x-bind:class="template.layer != 2 ? 'builder-component-thumb' : ''" x-html="window.wrapSvg(template.svg)">
                                </div>
                                <dl>
                                    <dt class="has-mb" x-text="getLayerName(template.layer)"></dt>
                                    <dd class="has-mb" x-text="template.name"></dd>
                                    <dt>Rarity</dt>
                                    <dd x-text="template.rarity"></dd>
                                    <dt>Used by</dt>
                                    <dd x-text="template.minted + '/' + template.max_mintable"></dd>
                                </dl>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="sportvatar-traits" x-show="hasSportbitsEquipped()">
                <div class="container">
                    <div class="title-row">
                        <h2>Equipped Sportbits</h2>
                    </div>

                    <div class="thumb-hold">
                        <template x-for="layer in sportbitLayers()" :key="layer.id">
                            <div class="thumb" x-bind:class="getComponentOptionalClass(layer.id)" x-show="hasSportbit(layer.id)">
                                <div class="img-hold" x-bind:class="layer.name != 'background' ? 'builder-component-thumb' : ''" x-html="templateOptionalSvg(layer.id)">
                                </div>
                                <dl>
                                    <dt class="has-mb">Name</dt>
                                    <dd class="has-mb" x-text="templateOptionalName(layer.id)"></dd>
                                    <dt>Category</dt>
                                    <dd x-text="layer.name"></dd>
                                    <dt>Rarity</dt>
                                    <dd x-text="templateOptionalRarity(layer.id)"></dd>
                                </dl>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>


    @include('partials.powered-by')

</x-guest-layout>

