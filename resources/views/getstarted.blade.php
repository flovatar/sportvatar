<x-guest-layout>
    <section class="tab-section"
             x-data="getStartedData()"
             x-init="initGetStarted($dispatch);"
             @hashchange.window="loadHashParams();"
    >
        <div class="container">
            <div class="tab-frame">
                <ul class="tabset">
                    <li><a x-bind:class="getActiveClass('builder')" href="#builder">{{ __('Building a Sportvatar') }}</a></li>
                    <li><a x-bind:class="getActiveClass('dapper')" href="#dapper">{{ __('Setting up a Dapper Wallet') }}</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab" x-bind:class="getTabClass('builder')" id="building-Sportvatar">
                        <div class="media-a alt sm">
                            <div class="img lg">
                                <img src="/images/rarity-flame-legendary.svg" alt="">
                            </div>

                            <div class="desc">
                                <div class="inner">
                                    <strong class="subtitle">{{ __('1 Sport Flame = 1 Sportvatar') }}</strong>
                                    <h2>{{ __('It all begins with a Sport Flame') }}</h2>
                                    <p>{{ __('The Sport Flame is the secret ingredient to build a Sportvatar.') }}</p>
                                    <p>{{ __('You can find a Flame inside each pack or you can buy one from other users on the Marketplace.') }}</p>
                                    <p>{{ __('Burn a Flame to give birth to your Sportvatar.') }}</p>
                                </div>
                            </div> 
                        </div>
                        <div class="media-a sm">
                            <div class="img xl">
                                <img src="/images/help-rarity.svg" alt="">
                            </div>

                            <div class="desc">
                                <div class="inner">
                                    <strong class="subtitle">{{ __('Rarities') }}</strong>
                                    <h2>{{ __('Into the Legendary') }}</h2>
                                    <p>{{ __('Everything in Sportvatar has four different rarity levels that are color coded: Common-White, Rare-Blue, Epic-Yellow, Legendary-Purple.') }}</p>
                                    <p>{{ __('While building your Sportvatar, you can choose Traits that belong to the same or lower rarity of the Sport Flame.') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="media-a alt sm">
                            <div class="img">
                                <img src="/images/hero/sportvatar-hero-character.svg" alt="">
                            </div>

                            <div class="desc">
                                <div class="inner">
                                    <strong class="subtitle">{{ __('Make your very own') }}</strong>
                                    <h2>{{ __('It gets creative in the Builder') }}</h2>
                                    <p>{{ __('Sportvatars are made of seven Traits: Body, Clothing, Eyes, Nose, Mouth, Hair and Facial Hair. Go into the Builder to play among billions of combinations for your Sportvatar.') }}</p>
                                    <p>{{ __('Each Trait is a limited edition. Once reached the maximum amount of usage, no new Sportvatar can use that specific Trait.') }}</p>
                                    <p>{{ __('Be wise, though. Sportvatars are immutable and have the uniqueness guaranteed by the blockchain.') }} </p>
                                </div>
                            </div>
                        </div>
                        <div class="media-a sm">
                            <div class="img">
                                <img src="/images/sportvatar-accessories.svg" alt="">
                            </div>

                            <div class="desc">
                                <div class="inner">
                                    <strong class="subtitle">{{ __('The Sportbits') }}</strong>
                                    <h2>{{ __('Customizing your look with accessories') }}</h2>
                                    <p>{{ __('Sportbits are optional accessories that you can add and remove from your Sportvatar at any time. While a Trait is forever, there is a Sportbit perfect for any occasion.') }}</p>
                                    <p>{{ __('Look out for special combos and community challenges that will require Sportvatars to equip different Sportbits each time!') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="media-a sm alt">
                            <div class="img lg">
                                <img src="/images/help/help-marketplace.png" alt="">
                            </div>

                            <div class="desc">
                                <div class="inner">
                                    <strong class="subtitle">{{ __('After Minting') }}</strong>
                                    <h2>{{ __('Things you can do') }}</h2>
                                    <p>{{ __('You can view your Sportvatar in the community "Locker Room" or in your personal Collection.') }}</p>
                                    <p>{{ __('From your Sportvatar page you can do a number of things:') }}</p>
                                    <ul>
                                        <li>{{ __('Reveal your Sportvatar abilities') }}</li>
                                        <li>{{ __('Download your Sportvatar image in multiple formats') }}</li>
                                        <li>{{ __('Equip your Sportvatar with the Sportbit accessories') }}</li>
                                        <li>{{ __('Put your Sportvatar up for sale in the Marketplace') }}</li>
                                    </ul>
                                    <p><br>{{ __('Prepare your Sportvatars for a world of challenges and new exciting features to be announced in the future!') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="dapper-cta">
                            <h2>{{ __('Setup your Dapper wallet to get started') }}</h2>
                            <div class="btn-wrap">
                                <a href="#dapper" class="btn-plain">{{ __('How to create a Dapper wallet') }}</a>
                            </div>
                        </div>
                    </div>


                    <div class="tab" x-bind:class="getTabClass('dapper')" id="setup-blocto-wallet">
                        <div class="heading">
                            <h1>{{ __('Getting started with the') }}<span class="highlight-txt">Dapper Wallet</span></h1>
                            <p>{{ __('If you’re new to trading on the Flow blockchain then here\'s your step-by-step guide to setting up a Dapper Wallet.') }}</p>
                        </div>
                        <section class="timeline-section">
                            <strong class="timeline-title">{{ __('Registering with Dapper Wallet') }}</strong>
                            <div class="timeline">
                                <div class="timeline-heading">
                                    <span class="no">1</span>
                                    <h2>{{ __('Connect a') }} <span class="highlight-txt">Dapper <br> Wallet</span> {{ __('to Sportvatar') }}</h2>
                                    <p>{{ __('The Dapper wallet is the wallet of choice in the Flow blockchain ecosystem – it’s easy to set up and create an account - all you need is an email address to get started.') }}
                                    </p>
                                </div>
                                <div class="media-a alt sm">
                                    <div class="img help">
                                        <img src="/images/help/dapper-step1.png" alt="">
                                    </div>
                                    <div class="desc">
                                        <div class="inner">
                                            <h3>{{ __('Start connecting a wallet') }}</h3>
                                            <p>{{ __('Click the') }} <b>{{ __('Login / Signup') }}</b> {{ __('button to open the') }} <b>Dapper</b> {{ __('sign in popup') }}.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-a alt sm">
                                    <div class="img help">
                                        <img src="/images/help/dapper-step2.png" alt="">
                                    </div>
                                    <div class="desc">
                                        <div class="inner">
                                            <h3>{{ __('Enter your email address') }}</h3>
                                            <p>{{ __('When prompted enter your email address and click the') }} <b>{{ __('Sign in / Register') }}</b> {{ __('button') }}.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-a alt sm">
                                    <div class="img help">
                                        <img src="/images/help/dapper-step3.png" alt="">
                                    </div>
                                    <div class="desc">
                                        <div class="inner">
                                            <h3>{{ __('Enter a password') }}</h3>
                                            <p>{{ __('Choose a password for your account and click "Sign up" to complete the process') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div class="blocto-cta">
                            <h2>{{ __('You’re now ready to buy and use your Dapper Wallet') }}</h2>
                            <div class="btn-wrap">
                                <a href="{{ route('builder') }}" class="btn-plain">{{ __('Go to Builder') }}</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    @include('partials/newsletter')

    @include('partials/powered-by')

</x-guest-layout>
