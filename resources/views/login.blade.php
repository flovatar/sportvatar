<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sportvatar') }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5f3faf">
    <meta name="msapplication-TileColor" content="#5f3faf">
    <meta name="theme-color" content="#5f3faf">

    <meta name="description" content="Sportvatar is a next generation PFP crypto project where people can become creator and choose any combination they like." />
    <link rel="canonical" href="https://sportvatar.com/" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Sportvatar" />
    <meta property="og:description" content="Sportvatar is a next generation PFP crypto project where people can become creator and choose any combination they like." />
    <meta property="og:url" content="https://sportvatar.com/" />
    <meta property="og:site_name" content="Sportvatar" />
    <meta property="article:modified_time" content="2021-11-17T22:29:49+00:00" />
    <meta property="og:image" content="https://sportvatar.com/images/share.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="https://sportvatar.com/images/share.png" />
    <meta name="twitter:site" content="@sportvatar" />

    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">


    <!--fonts-->
    <link rel="stylesheet" href="https://use.typekit.net/dov2gwc.css">
    <link rel="preconnect" href="https://fonts.gstatic.com"><link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700&family=Staatliches&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script>
        var svgOpen = '{!! config('sportvatar.svgOpen') !!}';
        var svgClose = '{!! config('sportvatar.svgClose') !!}';
        var moonpayDomain = '{!! config('sportvatar.moonpayDomain') !!}';
        var moonpayParams = '{!! config('sportvatar.moonpayParams') !!}';
        var moonpayPublicKey = '{!! config('sportvatar.moonpayPublicKey') !!}';
        var packPrice = {{ config('sportvatar.price') }};
        var packPrice2 = {{ config('sportvatar.price2') }};
    </script>
    <script src="{{ mix('js/app.js') }}" defer></script>
    <script defer data-domain="sportvatar.com" src="https://plausible.io/js/plausible.js"></script>

    <script>
        var userAgent = navigator.userAgent || navigator.vendor || window.opera;

        function isIOS() {
            let isIOS = /iPad|iPhone|iPod/.test(navigator.platform) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
            return isIOS
        }

        function isAndroid() {
            return (/android/i.test(userAgent));
        }

        function sendMessageToUnity(message) {
            if (isIOS()) {
                try {
                    window.webkit.messageHandlers.UnityInAppBrowser.postMessage(message);
                } catch(err) {
                    console.log('Can not reach native code');
                }
            } else if (isAndroid()){
                UnityInAppBrowser.sendMessageFromJS(message);
            }
        }

        function handleLoginClick() {
            window.user = user;

            if(user.addr) {
                console.log('send message to unity ' + user.addr);
                sendMessageToUnity("true" + "," + user.addr);
            } else {
                handleLoginFailClick();
            }
        }

        function handleLoginFailClick() {
            var message = "Login failed";
            console.log('send message to unity ' + message);
            sendMessageToUnity("false" + "," + message);
        }
    </script>
</head>
<body class="{{request()->routeIs('home', 'builder') ? 'header-transparent ' : ''}}">
<div id="wrapper"
     x-bind:class="jsLoaded ? 'jsloaded' : ''"
     x-data="appData"
     x-init="initApp($dispatch); jsLoaded = true;"
     @user-check-finish.window="userCheckLoading = false;"
     @user-loaded.window="user = $event.detail.user; handleLoginClick();"
     @profile-loaded.window="profile = $event.detail.profile; userCheckLoading = false;"
     @logged-out.window="user = null; profile = null;"
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
>
    <div class="w1">

        <nav id="main-nav">
            <div class="container">
                <div class="nav-wrap nav-wrap-login">
                    <ul class="nav-login">
                       <button x-show="!loggedIn() && !userCheckLoading" @click.prevent.stop="fcl.signUp()" class="btn"><span class="inner">Connect Wallet</span></button>
                    </ul>
                    <div x-show="loggedIn() || userCheckLoading">
                        <div class="menu-drop">
                            <dl class="user-info">
                                <dt x-show="showProfileName && name()">name</dt>
                                <dd x-show="showProfileName && name()" ><a x-bind:href="'https://find.xyz/'+name()" target="_blank" x-text="name()"></a></dd>
                                <dt>wallet ID</dt>
                                <dd @click.prevent.stop="copyAddress()" x-text="address()"></dd>
                                <dt>balance</dt>
                                <dd x-text="balance()+' Flow'"></dd>
                            </dl>
                            <ul class="sub-nav">
                                <li><a href="#" @click.prevent.default="logout()" class="link-logout">Logout <i class="icon-arrow-circle"></i></a></li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </nav>


        <!-- Page Content -->
        <main>
        </main>
    </div>

    <div x-show="loading" class="fixed z-10 inset-0 flex items-center justify-center bg-black-100 bg-opacity-95 transition-opacity" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="loading relative flex justify-between items-center mb-50">
            <span class="left-wall bg-yellow-100 mb-20 w-12 rounded-5"></span>
            <span class="ball bg-pink-100 w-24 h-24 mb-13 rounded-full"></span>
            <span class="right-wall bg-yellow-100 mt-20 w-12 rounded-5"></span>
        </div>
    </div>


@include('partials/modals')


<!-- transaction loader -->
    <div class="loader-widget" x-bind:class="getToasterClass()">
        <span class="txt" x-text="waitingSeal ? 'Sealing Transaction' : 'Loading'"></span>
        <img class="spinner" src="/images/loader-white.svg" alt="">
    </div>
    <!-- end of transaction loader -->
</div>



@stack('modals')

@livewireScripts
</body>
</html>
