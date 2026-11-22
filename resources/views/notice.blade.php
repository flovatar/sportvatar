<x-guest-layout>
    <section class="tab-section" style="padding:120px 0 80px;">
        <div class="container">
            <div class="tab-frame">
                <div class="tab-content">
                    <div class="tab active">
                        <h1>{{ $title }}</h1>
                        <p>{{ $message }}</p>
                        <p><a class="btn btn-green" href="{{ route('homepage') }}"><span class="inner">{{ __('Back to the homepage') }}</span></a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
