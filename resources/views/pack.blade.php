<x-guest-layout>
    <section class="hero-pack">
        <div class="container">
            <div class="pack-img">
                <img src="/images/packs/pack-common-opening.svg" alt="">
            </div>
            <div class="features sales-features">
                <div class="intro">
                    <strong class="block-title">Join the Sportvatar community</strong>
                    <h1>Claim your pack</h1>
                    <div class="two-cols">
                        <div class="col">
                            <p>Take your Sportvatar to the next level with the Series 2 Flobits. Over 60 new items have been created, many of which are fully animated.</p>
                        </div>
                        <div class="col">
                            <p>Unlock hidden easter eggs when you combine different Sportbits with traits and other Sportbits, making your Sportvatar truly one of a kind!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(config('sportvatar.packSaleEnabled'))
    
    <section class="pre-sale-registration alt">
        <div class="bg">
            <img src="/images/space-bg.png" alt="">
        </div>
        <div class="container">
            <h2>Only 420 Flobit Packs will be released for the first drop</h2>
            <p class="txt">All packs are the same price and contain 8 Flobits</p>
            <div class="btn-hold">
                <a href="/pack/login" class="btn btn-pink btn-lg">
                    <span class="inner">BUY NOW</span>
                </a>
            </div>
            <span class="note">*You are guaranteed to receive at least one Epic Flobit in each pack.</span>
        </div>
    </section>
    @else
        <div class="newsletter">
            <div class="bg">
                <img src="/images/stars.png" alt="">
            </div>
            <div class="container">
                <div class="heading">
                    <h3>Stay up-to-date</h3>
                    <h2>Be the first to receive updates and sneak peaks about the Pack sale!</h2>
                </div>
                <div class="form-content">
                    <div class="form-content-holder">
                        <div class="join-discord">
                            <div class="ico-wrap">
                                <a href="{{ config('sportvatar.discordUrl') }}" target="_blank"><i class="icon-discord"></i></a>
                            </div>
                            <span class="txt">
                                    Join our <a href="{{ config('sportvatar.discordUrl') }}" target="_blank"> Discord</a>
                                </span>
                        </div>
                        <div class="form-col">
                            <div class="form-wrap">
                                <form action="{{ route('subscribe') }}" method="POST">
                                    <div x-show="showSubscription" class="form-row">
                                        <div class="input-hold">
                                            <input x-model="emailSubscription" name="email" type="email" placeholder="Email address">
                                        </div>
                                        <button @click.prevent.stop="submitSubscription" type="submit" value="Get Notified" class="btn btn-lilac"><span class="inner">Sign up</span></button>
                                    </div>
                                    <div x-show="!showSubscription" class="success-message">Thank you</div>
                                </form>
                            </div>
                            <span class="join-newsletter">Join our Newsletter</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif


    @include('partials/powered-by')


</x-guest-layout>
