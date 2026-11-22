<nav id="main-nav">
    <div class="container">
        <div class="logo">
            <a href="{{ route('homepage') }}"><img src="/images/sportvatar-logo.svg" alt="Sportvatar"></a>
        </div>
        <div class="nav-wrap">
            <li class="mobile-build">
                <a class="btn-o btn-green btn-slimline" href="{{ route('builder') }}">Build</a>
            </li>
            <button class="nav-opener"><i class="icon-menu"></i></button>
            <ul class="nav">
                <li class="{{request()->routeIs('getstarted') ? 'active ' : ''}}"><a href="{{ route('getstarted') }}">{{ __('How it works') }}</a></li>
                <li class="{{request()->routeIs('builder') ? 'active ' : ''}}"><a href="{{ route('builder') }}">{{ __('Builder') }}</a></li>
                <li class="{{request()->routeIs('marketplace') ? 'active ' : ''}}"><a href="{{ route('marketplace') }}">{{ __('Marketplace') }}</a></li>
                <li class="{{request()->routeIs('showroom') ? 'active ' : ''}}"><a href="{{ route('showroom') }}">{{ __('Locker Room') }}</a></li>
                <li class="{{ request()->routeIs('pack.comparison')  ? 'active ' : ''}}"><a href="{{ route('pack.comparison') }}">{{ __('Packs') }}</a></li>
                <li x-show="!loggedIn() && !userCheckLoading" class="jshidden">
                    <button @click.prevent.stop="connectWallet()" class="btn-o btn-green">{{ __('Login/Signup') }}</button>
                </li>
                <li class="mobile-subnav">
                    <div class="mobile-subnav-header">
                        Language
                    </div>
                    <div class="mobile-lang">
                        <a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ \Route::localizedUrl('en') }}">EN</a>
                        <a class="{{ app()->isLocale('it') ? 'active' : '' }}" href="{{ \Route::localizedUrl('it') }}">IT</a>
                        <a class="{{ app()->isLocale('es') ? 'active' : '' }}" href="{{ \Route::localizedUrl('es') }}">ES</a>
                    </div>
                </li>
            </ul>
            <div class="user-menu" x-show="loggedIn() || userCheckLoading">
                <button class="menu-opener"><i class="icon-user"></i></button>
                <div class="menu-drop">
                    <dl class="user-info">
                        <dt x-show="showProfileName && name()">Name</dt>
                        <dd x-show="showProfileName && name()" ><a x-bind:href="'https://find.xyz/'+name()" target="_blank" x-text="name()"></a></dd>
                        <dt>Wallet ID</dt>
                        <dd @click.prevent.stop="copyAddress()" x-text="address()"></dd>
                    </dl>
                    <ul class="sub-nav">
                        <li class="{{request()->routeIs('collection') ? 'active ' : ''}}"><a x-bind:href="(name() && false) ? '/collection/'+name() : '/collection/'+address()">{{ __('My Collection') }}</a></li>
                        <li class="{{request()->routeIs('transactions') ? 'active ' : ''}}"><a x-bind:href="'/transactions/'+address()">{{ __('My Transactions') }}</a></li>
                        <li><a href="#" @click.prevent.default="logout()" class="link-logout">{{ __('Logout') }} <i class="icon-arrow-circle"></i></a></li>
                    </ul>
                </div>
            </div>

            <div class="language-selector">
                <button class="menu-opener" >{{ app()->getLocale() }}</button>
                <div class="menu-drop">
                    <ul class="sub-nav">
                        <li><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ \Route::localizedUrl('en') }}">English</a></li>
                        <li><a class="{{ app()->isLocale('it') ? 'active' : '' }}" href="{{ \Route::localizedUrl('it') }}">Italiano</a></li>
                        <li><a class="{{ app()->isLocale('es') ? 'active' : '' }}" href="{{ \Route::localizedUrl('es') }}">Español</a></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</nav>
