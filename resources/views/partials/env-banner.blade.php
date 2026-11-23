{{-- Environment banner (e.g. APP_BANNER="TESTNET - Flow testnet, nothing here is real"). Empty on production. --}}
@if(config('app.banner'))
<div id="env-banner" style="position:fixed;left:0;right:0;bottom:0;z-index:2147483647;background:#c92a2a;color:#fff;text-align:center;font:600 13px/1.5 system-ui,-apple-system,sans-serif;padding:4px 8px;letter-spacing:.04em;pointer-events:none;">{{ config('app.banner') }}</div>
@endif
