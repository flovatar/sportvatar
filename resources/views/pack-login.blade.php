<x-guest-layout>
    <section class="hero-pack">
        <div class="container">
            <div class="pack-img">
                <img src="/images/packs/pack-common-opening.svg" alt="">
                <div class="info-wrap">
                    <div class="info-circle">
                        <div class="inner">
                            <strong>1,000</strong>
                            <span>Packs</span>
                        </div>
                    </div>
                    <div class="info-circle">
                        <div class="inner">
                            <strong>39.00</strong>
                            <span>Flow</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="buy-steps">
                <h1>Buy in three easy steps</h1>

                <ul class="steps">
                    <li x-bind:class="loggedIn() ? 'completed' : 'active'">
                        <div class="txt-wrap">
                            <span class="checkbox"><i class="icon-tick02"></i></span>
                            <h2>Connect your Dapper Wallet</h2>
                            <p>Please choose the Blocto or Lilico option to connect or create your Flow wallet with your email address (Dapper sale will be available as soon as the FLOW token is supported).</p>
                            <p>If it's the first time you visit the site, you will be asked to initialize your account before you can use the Sportvatar smart contract.</p>
                        </div>
                        <a x-show="!loggedIn()" @click.prevent.stop="if(!loggedIn()){connectWallet()}" href="#" class="btn-o btn-green btn-home-cta">Connect Wallet</a>
                    </li>
                    <li x-bind:class="loggedIn() ? '{{Auth::check() ?  'completed' : 'active' }}' : ''">
                        <div class="txt-wrap">
                            <span class="checkbox"><i class="icon-tick02"></i></span>
                            <h2>Get your ticket for the Queue</h2>
                            <p>Cryptographically sign a random string to be able to join the queue.</p>
                            <p>It sounds complicated but it’s as easy as a click of a button.</p>
                        </div>
                        @if(!Auth::check())
                        <a x-show="loggedIn()" @click.prevent.stop="if(loggedIn()){signMessage('{{ $signature }}')}" href="#" class="btn-o btn-green btn-home-cta">Get Ticket</a>
                        @endif
                    </li>
                    <li x-bind:class="loggedIn() ? '{{Auth::check() ?  'active' : '' }}' : ''">
                        <div class="txt-wrap">
                            <span class="checkbox"><i class="icon-tick02"></i></span>
                            <h2>Join the Queue to purchase</h2>
                            <p>You can now join the queue page to participate in the Flobit S2 Pack sale!</p>
                            <p>You will be given a position in the Queue, when you reach the front you will see a BUY NOW button - just click to purchase!</p>
                        </div>
                        @if(Auth::check())
                        <a href="{{Auth::check() ?  '/pack/buy' : '#' }}" class="btn-o btn-green btn-home-cta">Join Queue</a>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </section>

    @include('partials/powered-by')

</x-guest-layout>
