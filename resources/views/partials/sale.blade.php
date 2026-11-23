<div class="col">
    <a x-bind:href="getUrl(sale)" class="card" x-bind:class="getRarityColor(sale)">
        <div class="img-hold" x-bind:class="(isFlame(sale) || isSportvatar(sale) || isNumber(sale)) ? '' : 'builder-component-thumb'">
            <template x-if="!isNumber(sale)">
                <img x-bind:src="getSvgUrl(sale)" alt="">
            </template>
            <template x-if="isNumber(sale)">
                <span x-text="parseNumberSportbit(getCategory(sale))" class="number-thumb white"></span>
            </template>
        </div>
        <div class="wrap">
            <dl>
                <dt x-text="hasMint(sale) ? 'Mint:' : 'Total Minted:'"></dt>
                <dd x-text="getMint(sale)"></dd>
                <dt>Rarity Level</dt>
                <dd x-text="getRarityText(sale)"></dd>
                <dt x-text="isSportvatar(sale) ? 'Ability Score' : 'Name'"></dt>
                <dd x-text="isSportvatar(sale) ? getRarityScoreNumber(sale) : getCategory(sale)"></dd>
                <dt>Sale Price</dt>
                <dd x-text="formatPrice(sale.price)+' '+listingCurrencyLabel(sale)"></dd>
                <dt x-show="!canPayListing(sale)">Wallet</dt>
                <dd x-show="!canPayListing(sale)" x-text="requiredWalletName(sale)+' only'"></dd>
            </dl>
        </div>
    </a>
</div>
