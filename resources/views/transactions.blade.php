<x-guest-layout>

    <script>
        @if(isset($stats))
            window.stats = @json($stats);
        @endif
        @if(isset($templates))
            window.templates = @json($templates);
        @endif
    </script>

    <div class="transaction-history"
         x-data="salesData(window.templates, '{{ $address }}', window.stats)"
         x-init="initSales($dispatch)"
         @hashchange.window="loadSearchParams(); searchResults();"
    >
        <div class="container">

            @include('partials.marketplace-stats')

            <div class="transaction-table-row">
                <div class="title-row">
                    <h2 x-text="address ? 'Your Transactions' : 'All Marketplace Transactions'"></h2>
                </div>
                @include('partials.filters')
                <div class="transaction-table-wrap">
                    <div class="scroll-x">
                        <table class="transaction-table">
                            <tr>
                                <th class="desc alt">Description</th>
                                <th class="bought">Purchase Date</th>
                                <th class="value">Price</th>
                                <th class="flowscan">Flowscan</th>
                            </tr>
                            <template x-if="sales">
                                <template x-for="(sale,index) in sales" :key="index">
                                    <tr x-bind:class="(index%2==0 ? 'bg ' : '')+getColor(sale)">
                                        <td class="desc alt">
                                            <div class="desc-wrap">
                                                <div class="img-wrap" x-bind:class="getType(sale) == 'flobit' ? '' : 'no-decor'">
                                                    <img x-bind:src="getSvgUrl(sale)" />
                                                </div>
                                                <div class="txt-wrap">
                                                    <strong x-text="getType(sale)"></strong>
                                                    <span x-text="getName(sale)"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="bought">
                                            <strong x-text="sale.flow_buyer_address"></strong>
                                            <span x-text="parseDateTimeString(sale.created_at)"></span>
                                        </td>
                                        <td class="value" x-text="formatPrice(sale.price) + (sale.currency == 'FLOW' ? ' FLOW' : ' $')"></td>
                                        <td class="flowscan"><a x-bind:href="(sale.transaction_id) ? 'https://flowscan.org/transaction/'+sale.transaction_id : '#'" class="icon-link"></a></td>
                                    </tr>
                                </template>
                            </template>
                        </table>
                    </div>
                </div>
                @include('partials.pagination')
            </div>
        </div>
    </div>

    @include('partials/powered-by')
</x-guest-layout>
