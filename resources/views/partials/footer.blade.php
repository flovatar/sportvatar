<footer id="footer">
    <div class="container">
        <div class="footer-links-row">
            <ul class="footer-links">
                <li><strong class="title">Sportvatar</strong></li>
                <li><a href="{{ route('builder') }}">{{ __('Builder') }}</a></li>
                <li><a href="{{ route('marketplace') }}">{{ __('Marketplace') }}</a></li>
                <li><a href="{{ route('showroom') }}">{{ __('Locker Room') }}</a></li>
            </ul>
            <ul class="footer-links">
                <li><strong class="title">{{ __('GETTING STARTED') }}</strong></li>
                <li><a href="{{ route('getstarted') }}#builder">{{ __('Building a Sportvatar') }}</a></li>
                <li><a href="{{ route('getstarted') }}#dapper">{{ __('Setting up Dapper Wallet') }}</a></li>
            </ul>
            <ul class="footer-links">
                <li><strong class="title">{{ __('NEWS & INFO') }}</strong></li>
                <!-- <li><a target="_blank" href="https://blog.sportvatar.com">{{ __('Blog') }}</a></li> -->
                <li><a target="_blank" href="https://gazzetta.it">La Gazzetta dello Sport</a></li>
                <li><a target="_blank" href="https://marca.com">MARCA</a></li>
                <li><a href="mailto:info@sportvatar.com">{{ __('Contact Us') }}</a></li>
            </ul>
            <ul class="footer-links">
                <li><strong class="title">{{ __('LEGAL') }}</strong></li>
                <li><a href="{{ app()->getLocale() == 'it' ? route('privacy-it') : route('privacy') }}">{{ __('Privacy Policy') }}</a></li>
                <li><a target="_blank" href="{{ app()->getLocale() == 'it' ? '/CondizioniGenerali.pdf' : '/TermsAndConditions.pdf' }}">{{ __('Terms & Conditions') }}</a></li>
            </ul>
        </div>
        <div class="footer-bottom">

            <div class="footer-row">
                <div class="col">
                    <!-- <ul class="social">
                        <li><a href="https://apps.apple.com/us/app/sportvatar/id1598146099?l=en" target="_blank" class="icon-app-store"></a></li>
                        <li><a href="https://play.google.com/store/apps/details?id=com.sportvatar.mobileapp" target="_blank" class="icon-google-play"></a></li>
                    </ul> -->
                </div>
                <div class="col">
                    <ul class="social">
                        <li><a href="{{ config('sportvatar.twitterUrl') }}" target="_blank" class="icon-twitter"></a></li>
                        <li><a href="{{ config('sportvatar.discordUrl') }}" target="_blank" class="icon-discord"></a></li>
                        <li><a href="{{ config('sportvatar.mediumUrl') }}" target="_blank" class="icon-medium"></a></li>
                        <li><a href="{{ config('sportvatar.githubUrl') }}" target="_blank" class="icon-github"></a></li>
                        <li><a href="https://www.instagram.com/sportvatar/" target="_blank" class="icon-instagram"></a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-row">
                <div class="col">
                    <span class="f-text">&copy; 2023 <a href="/">Sportvatar S.r.l.</a> <span class="hidden-xs"> - All rights reserved</span></span>
                </div>
                <div class="col">
                    <span class="f-text">A Sportvatar / La Gazzetta dello Sport / MARCA / RCS creation</span>
                </div>
            </div>

        </div>
    </div>
</footer>
