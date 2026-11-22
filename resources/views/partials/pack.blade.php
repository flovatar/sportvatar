<div class="col">
    <button @click.prevent.stop="selectPackToOpen(pack)" class="card" x-bind:class="getPackRarity(pack)">
        <div class="img-hold">
            <img x-bind:src="getPackImage(pack)" />
        </div>
        <div class="wrap">
            <dl>
                <dt>Mint</dt>
                <dd x-text="'#'+pack"></dd>
                <dt>Type</dt>
                <dd x-text="getPackName(pack)"></dd>
            </dl>
        </div>
    </button>
</div>
