<x-guest-layout>

    <script>
        @if (isset($templates))
            window.templates = @json($templates);
        @endif
        @if (isset($sportvatars))
            window.sportvatars = @json($sportvatars);
        @endif
    </script>


    <div class="marketplace-section"
         x-data="showroomData(window.templates)"
         x-init="initShowroom($dispatch);"
         @hashchange.window="loadSearchParams(); searchResults();"
    >
        <div class="container">

            <div class="sportvatar-cards-row">
                <div class="title-row">
                    <h2>Minted sportvatars</h2>
                </div>

                <!--
                <ul class="collection-list">
                    <li x-bind:class="activeTab == 'sportvatar' ? 'active' : ''"><button @click.prevent.stop="activeTab = 'sportvatar'">Recently Minted</button></li>
                    <li x-bind:class="activeTab == 'collection' ? 'active' : ''"><button @click.prevent.stop="activeTab = 'collection'">Top Collections</button></li>
                </ul>
                -->

                @include('partials.filters-showroom')

                <ul class="sportvatar-card-list">
                    <template x-if="sportvatars">
                        <template x-for="sportvatar in sportvatars" :key="sportvatar.id">

                            <div class="col">
                                <a x-bind:href="window.itemUrl('sportvatars', sportvatar.flow_id, sportvatar.flow_address)" class="card" x-bind:class="sportvatar.rarity">
                                    <div class="img-hold">
                                        <img x-bind:src="'/api/image/'+sportvatar.flow_id" />
                                    </div>
                                    <div class="wrap">
                                        <dl>
                                            <dt>Mint</dt>
                                            <dd x-text="'#'+sportvatar.flow_id"></dd>
                                            <dt>Rarity</dt>
                                            <dd x-text="sportvatar.rarity"></dd>
                                            <dt>Ability Score</dt>
                                            <dd x-text="getRarityScore(sportvatar.flow_metadata)"></dd>
                                            <dt>Sale Price</dt>
                                            <dd x-html="getSportvatarPrice(sportvatar) ? getSportvatarPrice(sportvatar) : 'Not for sale'" x-bind:class="getSportvatarPrice(sportvatar) ? '' : 'text-red'"></dd>
                                        </dl>
                                    </div>
                                    <span x-show="getSportvatarPrice(sportvatar)" class="icon-cart"></span>
                                </a>
                            </div>

                        </template>
                    </template>
                </ul>
            </div>
            @include('partials.pagination')
        </div>
    </div>

    @include('partials/powered-by')

</x-guest-layout>
