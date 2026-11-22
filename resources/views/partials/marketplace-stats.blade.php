
<div class="marketplace-stats-row">
    <div class="title-row">
        <h2>Stats</h2>

        @if(request()->routeIs('marketplace'))
            <a href="/transactions" class="btn-outline">Previous sales</a>
        @else
            <a href="/marketplace" class="btn-outline">Marketplace</a>
        @endif
    </div>
    <div class="marketplace-stats">
        <ul class="marketplace-stats_list">
            <li>
                <span class="dt"><small>Total</small>Sales</span>
                <span class="dd"><span x-text="formatPriceNoDecimal(Math.round(stats.total))"></span> <small>USD</small></span>
            </li>
            <li>
                <span class="dt"><small>Number of</small>Sales</span>
                <span class="dd"><span x-text="formatPriceNoDecimal(Math.round(stats.count))"></span></span>
            </li>
            <li>
                <span class="dt"><small>Average</small>price</span>
                <span class="dd"><span x-text="formatPrice(stats.avg)"></span>  <small>USD</small></span>
            </li>
            <li>
                <span class="dt"><small>Highest</small>sale</span>
                <span class="dd"><span x-text="formatPriceNoDecimal(stats.max)"></span>  <small>USD</small></span>
            </li>
        </ul>
    </div>
</div>
