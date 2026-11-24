<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- No CSRF token here: pages are edge-cached / static. JS fetches one from /csrf-token (resources/js/bootstrap.js). --}}

    <title>{{ config('app.name', 'Sportvatar') }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#283f42">
    <meta name="msapplication-TileColor" content="#283f42">
    <meta name="theme-color" content="#283f42">



    <meta name="description" content="{{ __('Sportvatar is a next generation PFP crypto project where people can become creator and choose any combination they like.') }}" />
    @if(\App\Support\Headless::building())
    <link rel="canonical" href="{{ \App\Support\Headless::canonicalUrl() }}" />
    @else
    <link rel="canonical" href="https://sportvatar.com/" />
    @endif
    <meta property="og:locale" content="{{ app()->getLocale() }}" />
    <meta property="og:type" content="website" />
    @php($ogItem = trim($__env->yieldPushContent('og')))
    @if($ogItem !== '')
    {{-- item page: per-item title/url/image (static render on R2, see App\Support\ItemImages) --}}
    {!! $ogItem !!}
    @else
    <meta property="og:title" content="Sportvatar" />
    <meta property="og:description" content="{{ __('Sportvatar is a next generation PFP crypto project where people can become creator and choose any combination they like.') }}" />
    <meta property="og:url" content="{{ \App\Support\Headless::building() ? \App\Support\Headless::canonicalUrl() : 'https://sportvatar.com/' }}" />
    <meta property="og:site_name" content="Sportvatar" />
    <meta property="og:image" content="https://sportvatar.com/images/share.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="https://sportvatar.com/images/share.png" />
    @endif
    <meta name="twitter:site" content="@sportvatar" />

    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">


    <!--fonts-->
    <link rel="stylesheet" href="https://use.typekit.net/dov2gwc.css">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700&family=Staatliches&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script>
        var svgOpen = '{!! config('sportvatar.svgOpen') !!}';
        var svgClose = '{!! config('sportvatar.svgClose') !!}';
    </script>
    @if(\App\Support\Headless::building())
    {{-- static page on R2 (headless:build): runtime config for resources/js/headless.js --}}
    <script>window.HEADLESS = @json(\App\Support\Headless::bootstrap());</script>
    @endif
    <script src="{{ mix('js/app.js') }}" defer></script>

    <script defer data-domain="sportvatar.com" src="https://plausible.io/js/script.js"></script>

</head>
<body class="{{request()->routeIs('home', 'builder', 'pack', 'pack.buy', 'pack.login') ? 'header-transparent ' : ''}}">
@include('partials.env-banner')
<div id="wrapper"
     x-bind:class="jsLoaded ? 'jsloaded' : ''"
     x-data="appData"
     x-init="initApp($dispatch); jsLoaded = true;"
     @user-check-finish.window="userCheckLoading = false"
     @user-loaded.window="user = $event.detail.user; if($event.detail.wallet){ currentWallet = $event.detail.wallet; }"
     @profile-loaded.window="profile = $event.detail.profile; userCheckLoading = false;"
     @logged-out.window="user = null; profile = null; currentWallet = null;"
     @loading-start.window="loading = true; waitingSeal = false;"
     @waiting-seal.window="loading = false; waitingSeal = true;"
     @loading-completed.window="loading = false; waitingSeal = false; sealCompleted = true; setTimeout(()=>sealCompleted=false,3000);"
     @subscribed-email.window="showSubscription = false"
     @moonpay-signed.window="moonpaySignature = $event.detail.signature"
     @pack-sealing.window="sealingPack = true"
     @pack-purchased.window="packPurchased = true"
     @user-not-initialized.window="showInitPopup = true;"
     @metadata-not-initialized.window="showInitMetadataPopup = true;"
     @transaction-error.window="handleTransactionErrorMessage($event.detail.message)"
     @address-found.window="handleAddressFound($event.detail.profile)"
     @login-failed.window="showLoginResult = true"
     @reserve-failed.window="showReserveResult = true"
     @executing-transaction.window="txId = $event.detail.txId"
     @user-blacklisted.window="showBlacklistModal = true"
     @pack-claimed.window="packRedeemCode = ''"
>
    <div class="w1">

    @include('partials/navigation')

    <!-- Page Content -->
        <main class="{{request()->routeIs('homepage') ? 'main-home' : ''}}">
            {{ $slot }}
        </main>
    </div>

    <div x-show="loading" class="fixed z-10 inset-0 flex items-center justify-center bg-black-100 bg-opacity-95 transition-opacity" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="loading relative flex justify-between items-center mb-50">
            <span class="left-wall bg-yellow-100 mb-20 w-12 rounded-5"></span>
            <span class="ball bg-pink-100 w-24 h-24 mb-13 rounded-full"></span>
            <span class="right-wall bg-yellow-100 mt-20 w-12 rounded-5"></span>
        </div>
    </div>

    @if(!request()->routeIs('builder'))
        @include('partials/footer')
    @endif

    @include('partials/free-pack-banner')

    @include('partials/modals')


    <!-- transaction loader -->
    <div class="loader-widget" x-bind:class="getToasterClass()">
        <span class="txt" x-text="waitingSeal ? 'Sealing Transaction' : 'Loading'"></span>
        <img class="spinner" src="/images/loader-white.svg" alt="">
    </div>
    <!-- end of transaction loader -->
</div>



@stack('modals')

</body>
</html>
