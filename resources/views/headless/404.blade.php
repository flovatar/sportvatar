<x-guest-layout>
    {{-- 404 of the static headless site (the Worker answers unknown paths with this page and status 404) --}}
    <section class="tab-section">
        <div class="container">
            <div style="text-align:center; padding: 120px 0 80px;">
                <h1>{{ __('Page not found') }}</h1>
                <p>{{ __('The page you are looking for does not exist (or not yet - new Sportvatars appear within a minute).') }}</p>
                <p style="margin-top: 30px;"><a class="btn-o btn-green" href="/">Sportvatar</a> &nbsp; <a class="btn-o" href="/marketplace">{{ __('Marketplace') }}</a></p>
            </div>
        </div>
    </section>
</x-guest-layout>
