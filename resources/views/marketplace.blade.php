<x-guest-layout>
    <script>
        @if(isset($templates))
            window.templates = @json($templates);
        @endif
        @if(isset($stats))
            window.stats = @json($stats);
        @endif
    </script>
    <div class="marketplace-section"
         x-data="marketplaceData(window.templates, window.stats)"
         x-init="initMarketplace($dispatch);"
         @hashchange.window="loadSearchParams(); searchResults();"
    >
        <div class="container">
            @include('partials.marketplace-stats')

            <div class="sportvatar-cards-row">
                <div class="title-row alt-sm">
                    <div class="title-wrap">
                        <h2>For sale</h2>
                    </div>
                </div>

                @include('partials.filters')

                <div class="sportvatar-card-list">
                    <template x-if="sales">
                        <template x-for="sale in sales" :key="sale.id">
                            @include('partials.sale')
                        </template>
                    </template>
                </div>
            </div>

            @include('partials.pagination')

            <ul x-show="totalResults == 0" class="no-results-msg">
                <li>
                    <span class="transition-colors block text-nowrap">No Results</span>
                </li>
            </ul>

            <div x-show="!sales" class="loading-msg">
                <div class="w-3/4 m-auto">
                    <div class="text-3xl font-smile">Loading</div>
                    <div class="text-xl">Please wait a few moments</div>
                </div>
            </div>

        </div>
    </div>

    @include('partials/powered-by')


</x-guest-layout>
