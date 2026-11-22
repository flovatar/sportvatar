<x-guest-layout>
    <section class="hero-pack">
        <div class="container">
            <div class="pack-img">
                <img src="/images/pack_flobits_loop.svg" alt="">
                <div class="info-wrap">
                    <div class="info-circle">
                        <div class="inner">
                            <strong>39.00</strong>
                            <span>Flow</span>
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="!sealingPack">
                <div class="btn-panel-info">
                    <span class="loader">{{ $availableFlobit == 0 ? 'Sold out' : '( '.$availableFlobit.' / 1,000 still available )'}}</span>
                </div>
                <div class="btn-panel">
                    <button x-show="loggedIn()" @click.prevent.stop.debounce="reservePack('flobit')" class="btn btn-pink btn-lg">
                        <span class="inner">BUY NOW</span>
                    </button>
                    <button x-show="!loggedIn()" @click.prevent.stop.debounce="connectWallet()" class="btn btn-pink btn-lg">
                        <span class="inner">Connect your Wallet</span>
                    </button>
                </div>
            </div>
            <div x-show="sealingPack" class="jshidden">
                <div class="btn-panel" x-show="!packPurchased">
                    <span class="loader">Please wait a few floments <img src="/images/loader-green.svg" width="50px" alt=""></span>
                </div>
                <div class="btn-panel-info" x-show="packPurchased">
                    <span class="loader">Congratulations! You just purchased a Flobit S2 Pack.</span>
                </div>
                <div class="btn-panel" x-show="packPurchased">
                    <a x-bind:href="'/collection/'+address()" class="btn btn-purple btn-lg">
                        <span class="inner">VIEW your PACK</span>
                    </a>
                    <a x-bind:href="(isVipUser) ? '/pack/buy': '/pack/login'" class="btn btn-pink btn-lg">
                        <span class="inner">BUY ANOTHER</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('partials/powered-by')

</x-guest-layout>
