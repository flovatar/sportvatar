<x-guest-layout>
    <section class="tab-section"
             x-data="treasureHuntingData()"
             x-init="initTreasureHunting($dispatch);"
    >
        <div class="container">
            <div class="tab-frame">
                <div class="tab-content no-nav">
                    <div class="tab active" id="about-sportvatar">
                        @if(!session('message') && !session('error'))
                        <div class="heading">
                            <img src="/images/treasure-hunt-title.svg" alt="">
                            <p>Welcome to our epic journey into the Sportvatar storyworld that will lead you to unexpected places and to do things you could never have predicted. Some real mental effort will be needed to surmount certain challenges, but worry not, if you're quick enough you will be rewarded with something very special!</p>
                        </div>
                        @endif
                        @if(!session('message') && !session('error'))
                        <div class="option-row th-message">
                            <p>Before you start running, you need to find where to go! Equip the treasure map and do as told you so.</p>
                            <strong class="title">Please enter code</strong>
                            <form action="/treasure-hunting" method="POST">
                                @csrf
                                <div class="input-panel">
                                    <div class="input-box">
                                        <div class="input-wrap">
                                            <input type="text" name="secret" id="secret">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-icon btn-green"><span class="inner"><i class="icon-check"></i></span></button>
                                </div>
                            </form>
                        </div>
                        @endif

                        @if(session('message'))
                        <div class="blocto-cta">
                            <h2>Well done! <br></br>Let's move on now to the second clue</h2>
                            <div class="btn-wrap">
                                <p>{{ session('message') }}</p>
                            </div>
                        </div>
                        @endif

                        @if(session('error'))
                            <div class="blocto-cta">
                                <h2>Sorry, the code you entered is not valid.</h2>
                                <div class="btn-wrap">
                                    <a href="/treasure-hunting" class="btn-plain">Try again</a>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>

            </div>

        </div>
    </section>

    @include('partials/powered-by')

</x-guest-layout>
