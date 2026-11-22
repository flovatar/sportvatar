# Sportvatar web app

Laravel 12 (PHP 8.5) monolith for sportvatar.com: Blade + Alpine + FCL front end (Dapper wallet, DUC),
Flow mainnet contracts on `0xca5c31c0c03e11be` (`cadence/`), MySQL, Redis queues, EN/IT/ES.

- Localized routes: `Route::localized()` in `routes/web.php` (EN without prefix, `/it`, `/es`), implemented in
  `App\Providers\LocalizationServiceProvider` (route names are `{locale}.{name}`; `route('builder')` follows the
  current locale). Translations: `resources/lang/{it,es}.json`.
- Chain data comes from the built-in indexer: `app/Services/Flow/*`, `flow:stream` (daemon), `flow:catchup`
  (every 5 min), `flow:process` / `ProcessPendingFlowEvents` (queue `flow-events`, one worker, chain order) feeding
  `App\Jobs\ProcessGraffleEvents`. Cadence scripts run through the vendored Flow CLI wrapper (`packages/flow-php-sdk`).
- Admin: Filament panel at `/admin` (`app/Filament`), wallet login only, allowlist `ADMIN_ADDRESSES`.
  Chain-derived resources are read-only.
- Flags (`config/sportvatar.php`): pack sales/claims, image export/proxy, incident blocklist (`flagged_items`).
- Build: `npm run production` (Laravel Mix 6). Tests: `php artisan test`.

See AGENTS.md for the Laravel Boost guidelines.
