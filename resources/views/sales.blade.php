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

        <div class="hero-wrapper">
            <section data-origin-x="0" data-origin-y="0" class="sales-hero" id="sales-hero-box">
                <div></div>
                <div class="sky" data-depth="0.1">
                    <img src="/images/sale-hero/hero-sky.svg" alt="">
                </div>
                <div class="island-very-far" data-depth="0.2">
                    <img src="/images/sale-hero/hero-floating-islands-very-far.svg" alt="">
                </div>
                <div class="island-far" data-depth="0.3">
                    <img src="/images/sale-hero/hero-floating-islands-far.svg" alt="">
                </div>
                <div class="island-city" data-depth="0.4">
                    <img src="/images/sale-hero/hero-flova-city-island.svg" alt="">
                </div>
                <div class="island-desert" data-depth="0.5">
                    <img src="/images/sale-hero/hero-floating-island-desert.svg" alt="">
                </div>
            </section>
        </div>

        <section class="features sales-features">
            <div class="container">
                <div class="intro">
                    <strong class="block-title">Sportvatar Land Pre-Sale</strong>
                    <h1>FlovaLabs are launching the TerraKit<sup>TM</sup> <br>Register here for the pre-sale</h1>
                    <div class="two-cols">
                        <div class="col">
                            <p>Flovalabs have identified an asteroid belt in a nearby barren sector of the Flovaverse as the optimal location for the deployment of their new terraforming product, the TerraKit.</p>
                        </div>
                        <div class="col">
                            <p>Each TerraKit will contain the deeds to an asteroid, a custom robot used to terraform the rock and a FUB (Sportvatar Universe Builder) that will begin to generate new life.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <div class="video-wrap">
            <div class="container">
                <a href="#" class="video play-video" data-video="/images/loop.mp4">
                    <img src="/images/video-cover.jpg" alt="">
                    <div class="icon-play">
                        <img src="/images/icon-play.svg" alt="">
                    </div>
                </a>
            </div>
        </div>

        <section class="features-list sales-features-list">

            <div class="rows">
                <div class="container">
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="bg" style="transform: rotate(19deg);">
                                    <img src="/images/feature-bg-swirl.svg" width="580" alt="" class="rotating">
                                </div>
                                <div class="wrap">
                                    <img src="/images/TerraKit-pulsating.svg" width="540" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">The TerraKit</strong>
                            <h2>Breathing life into new sectors of the flovaverse</h2>
                            <div class="highlight-txt">
                                <p>The TerraKit is Flova Labs latest invention designed to help colonize previously uninhabitable sectors of the Flovaverse.</p>
                            </div>
                            <p>Planet Flova’s eco-system is in peril and  it’s imperative that alternate off-planet colonies are established to ensure the continuation of Flovan life as we know it.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="bg" style="transform: rotate(19deg);">
                                    <img src="/images/feature-bg-swirl.svg" width="580" alt="" class="rotating">
                                </div>
                                <div class="wrap">
                                    <img class="img-desert" src="/images/island-desert.svg" width="619" alt="">
                                    <img class="img-rock" src="/images/island-rock.svg" width="619" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">The Land</strong>
                            <h2>Customize your own Floating space rock</h2>
                            <div class="highlight-txt">
                                <p>Be a space pioneer in helping colonize a barren asteroid belt within the Flovaverse.</p>
                            </div>
                            <p>Your asteroid island(s) will be your personal digital home where you showcase and interact with all your new Sportvatar Universe creations.</p>
                            <p>You can add Sportvatars of course, but you can also add in future Sportvatar series, the FlovaBots, pet companions, houses, spaceships, and much more.</p>
                            <p>Just as with Series 1 Sportvatars these islands will begin life in 2D, but will at a later date turn into a more immersive 3D world.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="bg" style="transform: rotate(71deg);">
                                    <img src="/images/feature-bg-swirl.svg" width="510" alt="" class="rotating">
                                </div>
                                <div class="wrap">
                                    <img src="/images/robot-changing.svg" width="726" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">The Flobot Constructor Kit</strong>
                            <h2>Configure your own flobot helper</h2>
                            <div class="highlight-txt">
                                <p>Your trusty Flobot will help terraform your new floating space rock as well as setting up your FUB.</p>
                            </div>
                            <p>You will be able to use the familiar Sportvatar builder to create your own unique Flobot choosing from 5 different body parts offering over X million possible combinations.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="bg" style="transform: rotate(19deg);">
                                    <img src="/images/feature-bg-swirl.svg" width="580" alt="" class="rotating">
                                </div>
                                <div class="wrap">
                                    <img src="/images/custom-FUB.png" width="575" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">The FUB</strong>
                            <h2>NoW you have the power to make your own NFTs</h2>
                            <div class="highlight-txt">
                                <p>The FUB (Sportvatar Universe Builder) is a fully customizable version of the now famous Sportvatar Series 1 Builder</p>
                            </div>
                            <p>That means you can use the innovative NFT builder technology of Sportvatar to create your very own NFT series that other people can then use to make their own creations.</p>
                            <p>The sky’s the limit as you can literally make anything that your imagination can come up with.</p>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <section class="features-list sales-features-list skew-bg-purple">

            <div class="rows">
                <div class="container">
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="bg" style="transform: rotate(19deg);">
                                    <img src="/images/feature-bg-swirl-dark.svg" width="523" alt="" class="rotating">
                                </div>
                                <div class="wrap">
                                    <img src="/images/combine-islands.png" width="612" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">Merging TerraKit Components</strong>
                            <h2>merge components together to make them bigger or more powerful </h2>
                            <div class="highlight-txt">
                                <p>Before a component is activated multiple ones can be merged together to make a new larger more powerful version.</p>
                            </div>
                            <p>Merge land deeds together to take charge of larger asteroids, merge Flobot Constructor Kits together to unlock rarer traits, or even merge FUBs together to increase the number of empty slots available for the designs in your builder.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="bg" style="transform: rotate(71deg);">
                                    <img src="/images/feature-bg-swirl-dark.svg" width="510" alt="" class="rotating">
                                </div>
                                <div class="wrap">
                                    <div class="tokens">
                                        <div class="token dust">
                                            <img src="/images/token-dust.png" width="250" alt="">
                                        </div>

                                        <div class="token dust2">
                                            <img src="/images/token-dust2.png" width="290" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">Monetizing Creativity</strong>
                            <h2>MONETIZE the creations of YOUR imagination</h2>
                            <div class="highlight-txt">
                                <p>Once you publish your FUB anyone can then use it to create their own unique NFTs </p>
                            </div>
                            <p>In order to mint NFTs using your builder users will have to pay you $DUST, and the amount they have to pay will be determined by you during the publishing process.</p>
                            <p>As and when new FUBs are published they will appear as additional builders within the Builder section of the Sportvatar website - kind of like an app store for NFT builders.</p>
                            <p>All of the components are completely tradable as separate NFTs so just like with Flames and Boosters in the Sportvatar Genesis packs you can choose to use them or sell them.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="img-col">
                            <div class="img">
                                <div class="wrap">
                                    <img src="/images/creation-of-david.svg" width="540" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="txt-col">
                            <strong class="block-title">Web 3 Revolution</strong>
                            <h2>You can now Give Life to the flovaverse</h2>
                            <div class="highlight-txt">
                                <p>We believe an audience can be much more than just a passive consumer of content.</p>
                            </div>
                            <p>Imagine the Cantina Bar in Star Wars where all the alien life-forms had been created by different members of the audience, or the Replicator in Star Trek that viewers could use to insert objects directly into the episode as it unfolded, if you can, then you can begin to imagine our vision for the Flovaverse!</p>
                            <p>We have built a tool that allows you to create your own NFTs, so you now have the chance to bring life to the Flovaverse itself, with your creations forming part of the fabric of the Sportvatar storyworld.</p>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <section class="pre-sale-registration">
            <div class="bg">
                <img src="/images/space-bg.png" alt="">
            </div>
            <div class="container">
                <strong class="block-title">Pre-sale registration</strong>
                <h2>20,000 pre-sale TerraKits<sup>TM</sup> are available at half-price!</h2>
                <div class="price-row">
                    <div class="price-col strike">
                        <strong class="price">100</strong>
                        <div class="desc">
                            <strong>Flow</strong>
                            <span>Full Price</span>
                        </div>
                    </div>
                    <div class="price-col">
                        <strong class="price">50</strong>
                        <div class="desc">
                            <strong>Flow</strong>
                            <span>Pre Sale</span>
                        </div>
                    </div>
                </div>
                <div class="btn-hold">
                    <a href="" class="btn btn-pink btn-lg">
                        <span class="inner">Register Now</span>
                    </a>
                </div>
                <p>Each component of the TerraKit will be a Voucher NFT that will be redeemable and transformed into its final form as it becomes available over the coming months:</p>
                <dl>
                    <dt>Sep 2022</dt>
                    <dd>Flovabot constructor kit</dd>
                    <dt>Dec 2022</dt>
                    <dd>Asteroid Island</dd>
                    <dt>Q1 2023</dt>
                    <dd>FUB - Sportvatar universe builder</dd>
                </dl>
                <p>Check out our <a href="#">TerraKit blog post</a> for full details of the pre-sale and an in-depth view of how it will work.</p>
            </div>
        </section>


        @include('partials/powered-by')

    </div>

</x-guest-layout>
