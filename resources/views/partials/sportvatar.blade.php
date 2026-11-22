<div class="col">
    <a x-bind:href="'/sportvatars/'+sportvatar.id+'/'+address" class="card" x-bind:class="getRarityText(sportvatar)">
        <div class="img-hold">
            <div>
                <img x-bind:src="'/api/image/'+sportvatar.id">
            </div>
        </div>
        <div class="wrap">
            <dl>
                <dt>Mint</dt>
                <dd x-text="'#'+sportvatar.id"></dd>
                <dt>Rarity</dt>
                <dd x-text="getRarityText(sportvatar)"></dd>
                <dt>Ability Score</dt>
                <dd x-text="getRarityScore(sportvatar)"></dd>
                <dt>Sale Price</dt>
                <dd x-html="sportvatar.price ? formatPrice(sportvatar.price)+' <span>Flow</span>' : 'Not for sale'" x-bind:class="sportvatar.price ? '' : 'text-red'"></dd>
            </dl>
        </div>
        <span x-show="sportvatar.price" class="icon-cart"></span>
    </a>
</div>
