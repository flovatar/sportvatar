<x-guest-layout>
    <script>
        @if(isset($templates))
            window.templates = @json($templates);
        @endif
        @if(isset($saleOffers))
            window.saleOffers = @json($saleOffers);
        @endif
    </script>


    <div
            x-data="collectionData('{{$address}}', window.templates, window.saleOffers)"
            x-init="initCollection($dispatch, '{{$address}}')"
            @collections-loaded.window="loadCollections($event.detail.collections)"
            @collections-pack-loaded.window="loadCollectionsPack($event.detail.collections)"
            @collections-sportvatar-loaded.window="loadCollectionsSportvatar($event.detail.collections)"
            @collections-sportbit-loaded.window="loadCollectionsSportbit($event.detail.collections)"
            @collections-claimables-loaded.window="loadClaimables($event.detail.claimables)"
            @pack-opening-started.window="startPackOpening()"
            @pack-opened.window="handlePackOpening()"
            @inbox-claimed.window="handleInboxClaimed()"
            @inbox-sportbit-claiming.window="showPackAnimation = true;"
            @inbox-sportbit-claimed.window="handleInboxClaimed()"
            @collections-sportvatar-failed.window="collectionSportvatarFailed = true; loadSportvatarFromIds();"
            @collections-sportvatar-ids-loaded.window="loadCollectionsSportvatarIds($event.detail.collections)"
            @sportvatar-loaded.window="loadSingleSportvatar($event.detail)"
    >
        <!-- My collection Page -->
        <div class="my-collection">
            <div class="container">

                @if(false)
                    <div class="info-box-hold">
                        <div class="airdrop-panel">
                            <div class="item-box">
                                <div class="item"><img src="/images/airdrop.svg" alt="image description"></div>
                                <span class="count" x-text="airdropFlobits()" x-show="airdropFlobits() > 0"></span>
                            </div>

                            <div class="btn-wrap">

                                <button x-show="isCollectionOwner()" @click.prevent.stop="showAirdropModal = true" class="btn pink-alt" x-bind:disabled="airdropFlobits() <= 0"><span class="inner" x-text="airdropFlobits() > 0 ? 'Claim all Airdrops' : 'No airdrops'"><span class="long-label">Claim all Airdrops</span><span class="short-label">Claim</span></span></button>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="collection">
                    <div class="title-row">
                        <h2>Your Collection</h2>
                    </div>
                    <div class="collection-select">
                        <select x-model="activeTab">
                            <option value="sportvatar" x-text="'Sportvatar ('+sportvatarCountTotal()+')'"></option>
                            <option value="flame" x-text="'Sport Flames ('+flameCountTotal()+')'"></option>
                            <option value="sportbit" x-text="'Sportbits ('+sportbitCountTotal()+')'"></option>
                            <option value="pack" x-text="'Packs ('+packCountTotal()+')'"></option>
                        </select>
                        <ul class="collection-list">
                            <li x-bind:class="activeTab == 'sportvatar' ? 'active' : ''"><button @click.prevent.stop="activeTab = 'sportvatar'">Sportvatars  <span class="count" x-text="sportvatarCountTotal()"></span></button></li>
                            <li x-bind:class="activeTab == 'flame' ? 'active' : ''"><button @click.prevent.stop="activeTab = 'flame'">Sport Flames  <span class="count" x-text="flameCountTotal()"></span></button></li>
                            <li x-bind:class="activeTab == 'sportbit' ? 'active' : ''"><button @click.prevent.stop="activeTab = 'sportbit'">Sportbits  <span class="count" x-text="sportbitCountTotal()"></span></button></li>
                            <li x-bind:class="activeTab == 'pack' ? 'active' : ''"><button @click.prevent.stop="activeTab = 'pack'">Packs  <span class="count" x-text="packCountTotal()"></span></button></li>
                        </ul>
                    </div>
                </div>


                <div x-show="activeTab == 'sportvatar'">
                    @include('partials.filters-collection-sportvatar')
                    <div class="sportvatar-card-list">
                        <template x-for="sportvatar in filteredSportvatars()" :key="sportvatar.id">
                            @include('partials.sportvatar')
                        </template>
                    </div>
                    <div x-show="sportvatars.length == 0">
                        <span class="no-results-msg">{{ __('No Sportvatars yet.') }} <a href="/builder" class="underline">{{ __('Go create some!') }}</a></span>
                    </div>
                </div>


                <div x-show="activeTab == 'flame'">
                    <div class="sportvatar-card-list">
                        <template x-for="component in componentsFlame()" :key="component.id">
                            @include('partials.component-collection')
                        </template>
                    </div>
                    <div x-show="componentsFlame().length == 0">
                        <span class="no-results-msg">{{ __('No Sport Flames here.') }} <a href="/marketplace" class="underline">{{ __('Go get some!') }}</a></span>
                    </div>
                </div>


                <div x-show="activeTab == 'sportbit'">
                    @include('partials.filters-collection-sportbit')
                    <div class="sportvatar-card-list">
                        <template x-for="component in filteredSportbits()" :key="(component && component.id) ? component.id : ((component && component.templateId && component.name) ? component.name+component.templateId : component)">
                            @include('partials.component-collection')
                        </template>
                    </div>
                    <div x-show="componentsSportbit().length == 0">
                        <span class="no-results-msg">{{ __('No Sportbits here.') }} <a href="/marketplace" class="underline">{{ __('Go get some!') }}</a></span>
                    </div>
                </div>



                <div x-show="activeTab == 'pack'">
                    <div class="sportvatar-card-list">
                        <template x-for="pack in packs" :key="pack">
                            @include('partials.pack')
                        </template>
                    </div>
                    <div x-show="packs.length == 0">
                        <span class="no-results-msg">{{ __('No Packs yet. Please wait for our next drop to buy some!') }}</span>
                    </div>
                </div>


            </div>
        </div>

        @include('partials.powered-by')








        <div class="modal-wrapper pack-modal-wrapper jshidden" x-show="showAirdropModal">
            <div class="modal-content">
                <div class="modal-inner">
                    <div class="modal-pack-box">
                        <button x-show="!hidePackOpenBtn" @click.prevent.stop="showAirdropModal = false" class="close"><i class="icon-cross02"></i></button>
                        <div class="open-pack">
                            <div class="img-pack" x-bind:class="fadePackOpening ? 'fade-out' : ''" x-show="!fadePackCompleted">
                                @include('partials.pack-airdrop-svg')
                            </div>

                            <div class="sportvatar-card-list pack-content" x-show="fadePackCompleted" x-bind:class="fadePackCompleted ? 'fade-in' : ''">
                                <template x-for="component in packNewComponents" :key="component.id">
                                    <div class="col">
                                        <a x-bind:href="'/components/'+component.id+'/'+address" class="card" x-bind:class="getRarityColorComponent(component)">
                                            <div class="img-hold component" x-bind:class="getComponentOutlineClass(component)">
                                                <img x-bind:src="'/api/image/template/'+component.templateId" />
                                            </div>
                                            <div class="wrap">
                                                <dl>
                                                    <dt x-text="getComponentLayer(component)"></dt>
                                                    <dd x-text="getComponentName(component)"></dd>
                                                    <dt>{{ __('Rarity') }}</dt>
                                                    <dd x-text="component.rarity"></dd>
                                                    <dt>{{ __('Mint') }}</dt>
                                                    <dd x-text="getComponentMint(component)"></dd>
                                                    <dt>{{ __('Sale Price') }}</dt>
                                                    <dd class="text-red">{{ __('Not for sale') }}</dd>
                                                </dl>
                                            </div>
                                            <span x-show="component.price" class="icon-cart"></span>
                                        </a>
                                    </div>
                                </template>
                            </div>


                            <button x-show="!hidePackOpenBtn && !showPackAnimation && !showPackOpening && !fadePackOpening" @click.prevent.stop="claimAirdropFlobits()" class="btn btn-pink btn-lg"><span class="inner">Open Airdrop Pack</span></button>
                            <button x-show="fadePackCompleted" @click.prevent.stop="showAirdropModal = false" class="btn btn-pink btn-lg"><span class="inner">Back to your Collection</span></button>

                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div class="modal-wrapper pack-modal-wrapper jshidden" x-show="showPackModal">
            <div class="modal-content">
                <div class="modal-inner">
                    <div class="modal-pack-box">
                        <button x-show="!hidePackOpenBtn" @click.prevent.stop="showPackModal = false" class="close"><i class="icon-cross02"></i></button>
                        <div class="open-pack">
                            <div class="img-pack" x-show="!fadePackCompleted" x-bind:class="fadePackOpening ? 'fade-out' : ''">
                                @include('partials.pack-svg')
                            </div>
                            <div class="sportvatar-card-list pack-content" x-show="fadePackCompleted" x-bind:class="fadePackCompleted ? 'fade-in' : ''">

                                <template x-for="component in packNewComponents" :key="component.id">
                                    <div class="col">
                                        <a x-bind:href="'/components/'+component.id+'/'+address" class="card" x-bind:class="getRarityColorComponent(component)">
                                            <div class="img-hold component" x-bind:class="(component.layer != 0 && component.layer != 13) ? 'builder-component-thumb' : ''">
                                                <template x-if="component.layer != 13">
                                                    <img x-bind:src="'/api/image/template/'+component.templateId" alt="">
                                                </template>
                                                <template x-if="component.layer == 13">
                                                    <span x-text="parseNumberSportbit(component.name)" class="number-thumb white"></span>
                                                </template>
                                            </div>

                                            <div class="wrap">
                                                <dl>
                                                    <dt x-text="getComponentLayer(component)"></dt>
                                                    <dd x-text="getComponentName(component)"></dd>
                                                    <dt>{{ __('Rarity') }}</dt>
                                                    <dd x-text="component.rarity"></dd>
                                                    <dt>{{ __('Mint') }}</dt>
                                                    <dd x-text="getComponentMint(component)"></dd>
                                                    <dt>{{ __('Sale Price') }}</dt>
                                                    <dd class="text-red">{{ __('Not for sale') }}</dd>
                                                </dl>
                                            </div>
                                        </a>
                                    </div>
                                </template>

                            </div>
                            <div class="modal-pack-footer" :class="{ 'overlay': !fadePackCompleted }">
                                <button x-show="!hidePackOpenBtn && !showPackAnimation && !showPackOpening && !fadePackOpening" @click.prevent.stop="window.openPack(selectedPackToOpen); hidePackOpenBtn = true;" class="btn-green btn-o btn-pack-cta"><span class="inner">Open pack</span></button>
                                <a x-show="fadePackCompleted" href="/builder/" class="btn-green btn-o btn-pack-cta"><span class="inner">Create your Sportvatar</span></a>
                                <div class="modal-pack-msg" x-show="fadePackCompleted">
                                     <span class="pack-open-message">{!! __('You\'re all set! Learn more <a href="/how-it-works">how it works</a> or jump directly in the Builder to let the fun begin!') !!}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

    </div>


</x-guest-layout>

