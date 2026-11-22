<x-guest-layout>
    <section class="tab-section" style="padding:120px 0 80px;">
        <div class="container">
            <div class="tab-frame">
                <div class="tab-content">
                    <div class="tab active">
                        <h1>Sportvatar admin</h1>
                        @auth
                            <p>You are signed in as <code>{{ auth()->user()->email }}</code>, which is not an admin wallet.</p>
                        @else
                            <p>Sign in with your wallet (button in the top menu), then open the admin again.</p>
                        @endauth
                        <p>Only wallet addresses configured in <code>ADMIN_ADDRESSES</code> can open the admin panel.</p>
                        <p><a class="btn btn-green" href="{{ url('/admin') }}"><span class="inner">Open the admin</span></a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
