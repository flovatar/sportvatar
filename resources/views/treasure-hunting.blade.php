<x-guest-layout>
    <section class="tab-section"
             x-data="treasureHuntingData()"
             x-init="initTreasureHunting($dispatch);"
             @logged-in.window="loadTreasureHuntingPage()"
             @login-failed.window="failedTreasureHuntingLogin()"
    >
        <div class="container">
            <div class="tab-frame">
                <div class="tab-content no-nav">
                    <div class="tab active" id="about-sportvatar">
                        <div class="heading">
                            <img src="/images/treasure-hunt-title.svg" alt="">
                            <p>Before you continue, please verify that you are a Sportvatar and not a bot or a human.</p>
                            <div x-show="!loggedIn() && !userCheckLoading" class="meme-connect-wallet">
                                <button @click.prevent.stop="fcl.signUp()" class="btn"><span class="inner">Connect Wallet</span></button>
                            </div>

                            <div x-show="loggedIn() || userCheckLoading" class="meme-connect-wallet">
                                <button @click.prevent.stop="if(loggedIn()){signMessage('{{ $signature }}')}" class="btn"><span class="inner">Verify Sportvatar</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials/powered-by')

</x-guest-layout>
