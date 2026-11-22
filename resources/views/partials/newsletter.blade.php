<section class="newsletter-section">
        <div class="newsletter">
            <div class="container">
                <div class="heading">
                    <h3>{{ __('Coming up') }}</h3>
                    <h2>{!! __('Be the first to know<br>about the pack sales')  !!}</h2>
                </div>
                <div class="social-wrapper">
                    <div class="join-discord">
                        <div class="ico-wrap">
                            <a href="{{ config('sportvatar.discordUrl') }}" target="_blank"><i class="icon-discord"></i></a>
                        </div>
                        <span class="txt" x-show="false">
                            {{ __('Join') }} <a href="{{ config('sportvatar.discordUrl') }}" target="_blank"> Discord</a>
                        </span>
                    </div>
                    <div class="join-twitter">
                        <div class="ico-wrap">
                            <a href="{{ config('sportvatar.twitterUrl') }}" target="_blank"><i class="icon-twitter"></i></a>
                        </div>
                        <span class="txt" x-show="false">
                            {{ __('Join') }} <a href="https://twitter.com/sportvatar" target="_blank"> Twitter</a>
                        </span>
                    </div>
                </div>
                <div class="form-content">
                    <div class="form-content-holder">
                        
                        <div class="form-col">
                            <div class="form-wrap">
                                <form action="{{ route('subscribe') }}" method="POST">
                                    <div x-show="showSubscription" class="form-row">
                                        <div class="input-hold">
                                            <input x-model="emailSubscription" name="email" type="email" placeholder="{{ __('Email address') }}">
                                        </div>
                                        <button @click.prevent.stop="submitSubscription" type="submit" class="btn-o btn-green" x-bind:class="(consentSportvatar || consentRcs) ? '' : 'disabled'"><span class="inner">{{ __('Sign up') }}</span></button>
                                    </div>
                                    <div class="input-checkboxes form-row">
                                        <div>  
                                            <label class="form-control" for="checkbox-consent-sportvatar">
                                                <input type="checkbox" id="checkbox-consent-sportvatar" x-model="consentSportvatar">
                                                <span>{{ __('I would like to receive information and promotional communications from Sportvatar S.r.l.') }} - <a href="{{ app()->getLocale() == 'it' ? route('privacy-it') : route('privacy') }}" target="_blank">{{ __('privacy policy') }}</a></span>
                                               
                                            </label>
                                        </div>
                                        @if(app()->getLocale() == 'it')
                                        <div>
                                            <label class="form-control" for="checkbox-consent-rcs">
                                                <input type="checkbox" id="checkbox-consent-rcs" x-model="consentRcs">
                                                <span>{{ __('I would like to receive information and promotional communications from RCS Mediagroup') }} - <a href="https://www.gazzetta.it/privacy.shtml" target="_blank">{{ __('privacy policy') }}</a></span>
                                            </label>
                                        </div>
                                        @endif
                                    </div>
                                    <div x-show="!showSubscription" class="success-message">{{ __('Thank you') }}</div>
                                </form>
                            </div>
                            <span class="join-newsletter" x-show="false">{{ __('Join the newsletter') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>