<x-guest-layout>
    <script id="sportvatars">
        @if(isset($sportvatars))
        window.sportvatars = @json($sportvatars);
        @endif
        @if(isset($templates))
        window.templates = @json($templates);
        @endif
        
        window.assets_url = '/webgl/images/';
    </script>
    <div x-data="homepageData(window.sportvatars, window.templates)"
    x-init="initHomepage($dispatch)" 
    >
    
        <div class="main-home">
            <div class="hero-glow"></div>
            <div class="hero-lines">
                <div class="hero-lines-inner">
                    <svg id="lines" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 499.58">
                      <path id="line1" d="M1401.97,471.38c-184.07-25.21-417.23-40.28-670.87-40.28-281.45,0-537.68,18.55-729.6,48.93" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                      <path id="line2" d="M1.5,402.21c191.92-19.16,448.15-30.86,729.6-30.86,253.64,0,486.8,9.5,670.87,25.4" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                      <path id="line3" d="M1.5,328.8c191.92-11.74,448.15-18.91,729.6-18.91,253.64,0,486.8,5.82,670.87,15.57" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                      <path id="line4" d="M1401.97,248.54c-184.07,3.99-417.23,6.38-670.87,6.38-281.45,0-537.68-2.94-729.6-7.75" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                      <path id="line5" d="M1.5,173.23c191.92,11.74,448.15,18.91,729.6,18.91,253.64,0,486.8-5.82,670.87-15.57" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                      <path id="line6" d="M1.5,99.82c191.92,19.16,448.15,30.86,729.6,30.86,253.64,0,486.8-9.5,670.87-25.4" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                      <path id="line7" d="M1.5,23.02c191.92,30.38,448.15,48.93,729.6,48.93,253.64,0,486.8-15.07,670.87-40.28" style="fill: none; stroke: #d4ff36; stroke-miterlimit: 10;"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="hero-wrapper">
            <div class="hero-wrapper-inner">
            <div id="hero-container">
                <div id="stage">
                  <div id="shape" class="ring backfaces">
                    <div class="plane one"><div class="icon1"><img src="/images/hero/icon-basketball.png" alt=""></div></div>
                    <div class="plane two"><img src="/images/hero/icon-tennis.png" alt=""></div>
                    <div class="plane three"><img src="/images/hero/icon-soccer.png" alt=""></div>
                    <div class="plane four"><div class="icon2"><img src="/images/hero/icon-american-football.png" alt=""></div></div>
                    <div class="plane five"><img src="/images/hero/icon-cricket.png" alt=""></div>
                    <div class="plane six"><img src="/images/hero/icon-soccer.png" alt=""></div>
                    <div class="plane seven"><img src="/images/hero/icon-american-football.png" alt=""></div>
                    <div class="plane eight"><div class="icon1"><img src="/images/hero/icon-basketball.png" alt=""></div></div>
                    <div class="plane nine"><img src="/images/hero/icon-tennis.png" alt=""></div>
                    <div class="plane ten"><img src="/images/hero/icon-cricket.png" alt=""></div>
                    <div class="plane eleven"><img src="/images/hero/icon-basketball.png" alt=""></div>
                    <div class="plane twelve"><img src="/images/hero/icon-baseball.png" alt=""></div>
                  </div>
                </div>
            </div>
            <div id="hero-container2">
                <div id="stage">
                  <div id="shape" class="ring backfaces">
                    <div class="plane one"><div class="icon1"><img src="/images/hero/icon-basketball.svg" alt=""></div></div>
                    <div class="plane two"><img src="/images/hero/icon-tennis.svg" alt=""></div>
                    <div class="plane three"><img src="/images/hero/icon-soccer.svg" alt=""></div>
                    <div class="plane four"><div class="icon2"><img src="/images/hero/icon-american-football.svg" alt=""></div></div>
                    <div class="plane five"><img src="/images/hero/icon-cricket.svg" alt=""></div>
                    <div class="plane six"><img src="/images/hero/icon-soccer.svg" alt=""></div>
                    <div class="plane seven"><img src="/images/hero/icon-american-football.svg" alt=""></div>
                    <div class="plane eight"><div class="icon1"><img src="/images/hero/icon-basketball.svg" alt=""></div></div>
                    <div class="plane nine"><img src="/images/hero/icon-tennis.svg" alt=""></div>
                    <div class="plane ten"><img src="/images/hero/icon-cricket.svg" alt=""></div>
                    <div class="plane eleven"><img src="/images/hero/icon-basketball.svg" alt=""></div>
                    <div class="plane twelve"><img src="/images/hero/icon-baseball.svg" alt=""></div>
                  </div>
                </div>
            </div>
            </div>
        </div>
       
    
    @if(config('sportvatar.packSalesEnabled'))
    <div class="promo-bottom-right">
        <a href="{{ route('pack.comparison') }}" target="_blank" class="pack-sale-promo">
            <img src="/images/CTA-Homepage.svg" alt="">
        </a>
    </div>
    @endif

    <section class="features">
        <div class="container">
            <div id="sportvatar-character">
                <img src="/images/hero/sportvatar-hero-character.svg" class="hero-character" alt="" />
            </div>
            <div class="intro">
                <h1>{{ __('Create and collect your') }} <span class="highlight-txt">{{ __('digital athletes') }}</span></h1>
                <div class="two-cols">
                    <div class="col">
                        <p>{{ __('Have fun designing your Sportvatar among billions of possible combinations.') }}</p>
                    </div>
                    <div class="col">
                        <p>{{ __('MARCA, La Gazzetta dello Sport and Flovatar join forces in a new home for sport fans.') }}</p>
                    </div>
                </div>
                <a href="{{ route('builder') }}" class="btn-o btn-green btn-home-cta">{{ __('Start') }}</a>
            </div>
        </div>
    </section>


    <section class="features-list">
        <div class="rows">
            <div class="decor-bg-wrap">
                <div class="container">
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="wrap">
                                    <img src="/images/sportvatar-character-build.svg" width="556" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">{{ __('You are the creator') }}</strong>
                            <h2>{{ __('Sportvatars are custom made by you') }}</h2>
                            <p>{{ __('Play with the traits and secure your very own digital athlete. Sportvatars are guaranteed to be unique!') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="wrap">
                                    <div class="balls-wrapper">
                                        <div class="balls-spinning">
                                            <div class="sport-ball football">
                                                <img src="/images/hero/football.png" alt="" />
                                            </div>
                                            <div class="sport-ball cricket">
                                                <img src="/images/hero/cricket-ball.png" alt="" />
                                            </div>
                                            <div class="sport-ball shuttlecock">
                                                <img src="/images/hero/shuttlecock.png" alt="" />
                                            </div>
                                            <div class="sport-ball basketball">
                                                <img src="/images/hero/basketball.png" alt="" />
                                            </div>
                                            <div class="sport-ball american-football">
                                                <img src="/images/hero/american-football.png" alt="" />
                                            </div>
                                            <div class="sport-ball tennis">
                                                <img src="/images/hero/tennis-ball.png" alt="" />
                                            </div>
                                            <div class="sport-ball volleyball">
                                                <img src="/images/hero/volleyball.png" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">{{ __('Discover the Sportbits') }}</strong>
                            <h2>{{ __('Wear accessories to boost your Sportvatar') }}</h2>
                            <p>{{ __('Seek the ultimate look for your collectibles and start trading in the marketplace!') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="img-col">
                        <div class="media-a sm">
                            <div class="img dark">
                                <div class="wrap">
                                    <img src="/images/newspapers.png" width="613" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="txt-col">
                        <strong class="block-title">{{ __('Who we are') }}</strong>
                        <h2>{{ __('MARCA, La Gazzetta dello Sport and Flovatar') }}</h2>
                        <p>{{ __('RCS MediaGroup join forces with Flovatar, one of the top projects on the Flow blockchain.') }}</p>
                    </div>
                </div>
            </div>
        </div>


        <div class="container">
            <div class="cta-home-2">
                <a href="{{ route('getstarted') }}" class="btn-o btn-green btn-home-cta">{{ __('How it works') }}</a>
            </div>
        </div>
        
    </section>

    @include('partials/newsletter')
    
    @include('partials/powered-by')
    
</div>

</x-guest-layout>
