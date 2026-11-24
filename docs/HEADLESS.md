# Headless sportvatar.com — architecture (v1, testnet, 2026-11-24)

Same design as flovatar-web `docs/HEADLESS.md` (built the same night): **static first** (HTML on R2, regenerated per item),
**chain-first** reads in the browser (FCL), a **cached JSON API** only for cross-wallet questions, **visual design unchanged**
(the static pages ARE the Blade views, rendered by Laravel). Prod (`main`, sportvatar.com) runs the same code (classic site + static build to `site/`); the Worker switch is a Cloudflare step; nothing here changes
the server-rendered site's behaviour except the anonymous-page caching (section 2).

```
browser ──► Cloudflare Worker "sportvatar-headless" (testnet host: next-testnet.sportvatar.com)
              │  GET page/asset ──► R2 sportvatar-web/testnet/site/...  (static HTML/JS/CSS/images, built by Laravel)
              │  /api/*, /login*, /logout, /csrf-token, searches, /templates/*, /components/*, /pack*, ...
              │        └──► same-origin proxy ──► Laravel origin (testnet.sportvatar.com)
              │  /sportvatars/{id}/{owner} ──► 301 /sportvatars/{id}
              └─ og:image of every Sportvatar page = og.jpg on images.sportvatar.com (renderer pipeline)
browser ──► Flow access node (FCL): wallet contents, owner + live storefront listing of a Sportvatar, balances, packs
```

## 1. Pieces (code)

| Piece | Where |
|---|---|
| Edge-cacheable anonymous pages | `App\Support\PublicCache`, `Http\Middleware\{StartSession,PublicCacheHeaders,VerifyCsrfToken}`, `AuthController` (login nonce in the encrypted `sportvatar_login` cookie), `GET /csrf-token` + axios interceptor (`resources/js/bootstrap.js`) |
| Template SVGs out of the HTML | `GET /templates/data/{all|seriesN}.js?v=` (`TemplateDataController`, immutable) instead of ~33 MB inline JSON (1.3 MB gzip) on Sportvatar/collection/Builder pages |
| API v2 | `routes/api.php` prefix `v2`, `Api\V2\{PublicController,ItemController,AuthController}`, `Middleware\PublicApiCache` |
| Static build | `php artisan headless:build` (`Services\Headless\StaticSiteBuilder`, `ItemSnapshot`, `Support\Headless`) |
| Regeneration | `Support\HeadlessRegen` (model observers + indexer hook in `ProcessGraffleEvents`), `Jobs\RegenerateStaticItem`; scheduler: hourly shells (:43), nightly full build (03:40) |
| Browser | `resources/js/headless.js` (searchApi, itemUrl, resolveItemOwner), `resources/js/flow/find-item-owner.script.js` |
| Worker | `headless/worker/worker.js` + `wrangler.toml` |
| Tests | `tests/Feature/{HeadlessTest,PublicCacheTest}.php` (SQLite, no MySQL) |

## 2. Anonymous pages are cacheable (also on the classic site)

- Anonymous GETs (no session cookie) to public pages run with a throw-away in-memory session: **no Set-Cookie**, `Cache-Control:
  public, max-age=0, s-maxage=60, stale-while-revalidate=300`. Requests with a session: `private, no-store`. Session paths
  (`/login`, `/logout`, `/csrf-token`, `/pack*`, `/{it,es}/pack*`, `/admin*`, twitter, treasure hunting, `/api/v2/auth/*`) always get a session.
- No CSRF token in HTML; JS fetches `/csrf-token` before the first POST (retry once on 419). Read-only search POSTs
  (`/search`, `/locker-room/search`, `/leaderboard/search`, `/transactions/search`, `/builder/search`) are CSRF-exempt + sessionless.
- `/login/nonce` is sessionless (nonce + message in the encrypted `sportvatar_login` cookie, single use, 30 min) and returns
  `{nonce, message, authenticated, address, admin}`.
- **Locale**: stateless requests never use `Accept-Language` (the edge cache is keyed by URL): localized routes (`/`, `/it`, `/es`,
  `/{it,es}/how-it-works|builder|packs|meme-maker`) carry their language, other pages use `?lang=` or EN. Nav/footer links to the
  marketplace / Locker Room carry `?lang=` when the page is IT/ES. Logged-in users keep the session locale.
- Cloudflare only caches HTML with a Cache Rule (sportvatar.com has none yet → `cf-cache-status: DYNAMIC`); the rule must use
  "Browser TTL: respect origin".

## 3. API v2 (`/api/v2`, public GET, `public, max-age=0, s-maxage=60, stale-while-revalidate=300`, `Access-Control-Allow-Origin: *`, no cookies)

| Endpoint | Returns |
|---|---|
| `meta` | `{api:2, network, contracts.sportvatar, frontend_url, images_url, series, templates:{all, seriesN:{version, script}}, wallets, pack_claims{open, wallets}}` |
| `marketplace` | paginator (20) of open listings, params as `POST /search` (`templatecat, sportbitcat, templateid, rarity, currency, sortby, pricemin, pricemax, page`); lean rows (no svg) + `thumb` |
| `marketplace/stats` (= `stats`) | USD (Dapper) `{total, avg, count, max}` + `flow{...}`, `listings`, `sportvatars` |
| `transactions` | paginator (50) of sales (`/transactions/search` params + `address`) |
| `locker-room` | paginator (20) of Sportvatars (`rarity, sortby, mintmin, mintmax, layer, template`) |
| `templates?set=all|seriesN|builder` | lean template list (no svg/metadata) |
| `templates/{flowId}` | Sportbit type: `{template, minted, listings:[{currency,count,floor}], sales{count,avg,max,last_at}}` |
| `packs` | `[{series, name, flames, opened, unopened, total}]` — never secrets, signatures or vouchers |
| `rankings` | `{holders (top 25), top_sales (25), stats}` |
| `sportvatars/{id}`, `sportbits/{id}` (`components/{id}`) | snapshot `{type,id,name,owner,url,flagged,owner_blocked,images{og,full_png,thumb,svg,downloads},listing{price,currency,is_dapper,seller,listing_id,listed_at}|null,sales[],updated_at, + mint,rarity,ability,series,creator,stats,combination,layers | template,mint,destroyed,packed}` |

Auth (web group, session + CSRF, `private, no-store`): `GET auth/nonce`, `GET auth/csrf`, `GET auth/me` `{authenticated,address,admin}`,
`POST auth/login`, `POST auth/logout`. The front end keeps using `/login/nonce`, `/csrf-token`, `/login`, `/logout` (same logic).
**The frontend origin must be in `SPORTVATAR_ACCOUNT_PROOF_ORIGINS`** (testnet: `https://testnet.sportvatar.com,https://next-testnet.sportvatar.com`).
Free packs waiting: `GET /api/packs/pending/{address}` (existing, private no-store) — used by the banner on every page.

## 4. Static build (`headless:build`)

- Renders through the HTTP kernel (same controllers/middleware/views) with the frontend host, inside `Headless::begin()`; the layout
  prints `<link rel=canonical>`/`og:url` on the frontend host and `window.HEADLESS = {mode:'static', page, item:{type,id,owner,snapshot}, ...}`.
  Refuses to upload HTML containing a CSRF token (`csrf-token`, `_token`, Livewire's `data-csrf`); Livewire asset injection is disabled
  per render (Filament forces it process-wide once an admin page was rendered).
- Objects (`{prefix}site/`): shells `index.html`, `it/index.html`, `es/index.html`, `{,it/,es/}how-it-works|builder|packs/index.html`,
  `marketplace/`, `locker-room/`, `transactions/`, `hall-of-fame/`, `privacy/`, `privacy-it/`, `_shells/collection.html`, `404.html`;
  one `sportvatars/{id}/index.html` per Sportvatar (~42 KB each); `sitemap.xml`; `public/` assets + `js/app.js@{mix id}` bundle copies.
- **Not static (proxied, SSR)**: Sportbit item pages `/components/{id}/{owner}` (103k on mainnet; API `templates/{id}` is ready for type
  pages), `/pack*` (claims/purchases need a session), meme maker, leaderboard, mosaic, admin, `/transactions/0x...`, FIND names
  `/collection/{name}`, any `?lang=it|es` URL.
- Options as on Flovatar: `--assets --shells --items --sitemap`, `--page=`, `--type=sportvatar --ids=1,5-9 --limit=N`, `--out=/dir`,
  `--force`, `--dry-run`, `--quiet-puts`. Unchanged objects are skipped (md5 vs ETag). Off mainnet the prefix is forced under `testnet/`.
- Deploy script (testnet): `headless:build --assets --shells --sitemap` detached after the caches (log `storage/logs/headless-build.log`).
  Measured on testnet: first full upload 1,301 objects / 67 MB in 394 s; re-run 2 s (1 changed); 7 Sportvatar pages 2.6 s.

## 5. Chain-first in the browser

| Page | Live data |
|---|---|
| Sportvatar `/sportvatars/{id}` | `resolveItemOwner()`: one Cadence script over candidates (`?owner=` hint, snapshot owner, connected wallet, then API owner) returns the holder **and its live NFTStorefrontV2 listing** (`listingId, price, vault`) → the Alpine component switches owner/listing (`item-owner-resolved`, `item-listing-resolved`); Sell/Gift/Cancel/Buy use the existing tx JS (Flow Wallet FLOW txs, Dapper DUC txs, `detectWallet` branching unchanged) |
| Collection `/collection/0x…` | one shell; the existing `get-collections-*` scripts (Sportvatars, Sportbits, packs) for `HEADLESS_ROUTE.address` |
| Marketplace / Locker Room / transactions | `GET /api/v2/...` (same params + paginator shape as the POST searches) |
| Builder | template SVGs from `/templates/data/seriesN.js?v=` (proxied, immutable), checkout as before |
| Every page | `/login/nonce` (server login state), free-pack banner via `/api/packs/pending/{addr}` (Flow Wallet → green "N free packs waiting"; Dapper → amber "reserved"), silent account-proof login |

After a purchase/gift the page navigates to `/sportvatars/{id}?owner=0x…` (hint for the chain check).

## 6. Regeneration

Indexer applies an event → `HeadlessRegen::touch('sportvatar', id)` from (a) observers on `Sportvatar` saves (owner, metadata, name,
rarity), `SaleOffer` saves/deletes, new `Sale`s, and (b) `HeadlessRegen::fromEvent()` after each applied event (`Sportvatar.*`,
`NFTStorefrontV2.*` with a Sportvatar `nftType`, incl. `Listing.ResourceDestroyed` of a cancel, whose offer removal is a query-builder
delete). Debounced (`Cache::add` marker, 30 s delay, queue `default`); the job re-renders and uploads only on change; ownerless/missing
item → object deleted (Worker falls back to the origin). Safety nets: hourly shells, nightly full build. Verified on testnet:
fw1 listed Sportvatar #2 for 1.50 FLOW → page on R2 with the listing **140 s** after the tx; cancel → page without listing **172 s**.

## 7. Worker `sportvatar-headless`

Vars: `SITE` (R2 binding → `sportvatar-web`), `SITE_PREFIX` (`testnet/site/` | `site/`), `ORIGIN` (`https://testnet.sportvatar.com`),
`NOINDEX` (`true` on testnet), `BUCKET_URL` (only without the binding). Order: robots/health → 301 owner URLs → non-GET or PROXY list →
`?lang=it|es` → origin → `/collection/{name}` → origin, `/collection/0x…` → shell → R2 object → new Sportvatar not built yet → origin
fallback → asset not mirrored → origin → `404.html`. `X-Headless: r2|origin|origin-fallback`. Same-origin proxy keeps the Laravel
session cookie first-party; `cf.cacheEverything` only for user-agnostic GETs (`/api/v2` except auth, `/api/image`, `/templates`,
Sportbit pages, `/collection/api`, meme maker/leaderboard/mosaic, `?lang=` pages). Subrequests use a normal Chrome UA (the zone WAF
blocks bot-like agents). Such cacheEverything subrequests get the zone's Browser Cache TTL written over Laravel's `max-age=0`; the
Worker puts `max-age=0` back (otherwise browsers keep API JSON for hours). Proxied server-rendered pages get their own URL as
canonical/og:url (the classic layout prints the site-wide `https://sportvatar.com/`). Also: `www.` → apex 301, `/collectible/{id}`
(the contract's `ExternalURL`) → 301 `/sportvatars/{id}`.

**Production** (`wrangler.toml` `[env.production]`, Worker `sportvatar-headless-prod`): `SITE_PREFIX=site/`,
`ORIGIN=https://api.sportvatar.com` (Forge alias of the prod site, Cloudflare Origin CA certificate, its own `robots.txt` =
`Disallow: /`), `NOINDEX=false`, R2 binding `SITE` → `sportvatar-web`; attached to `sportvatar.com/*` with a **Worker route** (the
apex DNS record stays, so removing the route is an instant rollback to the classic site). The account-proof `appIdentifier` stays
`https://sportvatar.com` (no allowlist change). Prod `.env`: `HEADLESS_ENABLED=true`, `HEADLESS_FRONTEND_URL=https://sportvatar.com`,
`HEADLESS_PREFIX=` (empty → `site/`); the deploy script runs `headless:build --assets --shells --sitemap` detached.

## 8. Environment (`.env`)

`HEADLESS_ENABLED`, `HEADLESS_FRONTEND_URL`, `HEADLESS_PREFIX` (testnet: `testnet`), `HEADLESS_DISK=r2web`, `R2_WEB_BUCKET=sportvatar-web`,
`HEADLESS_DEBOUNCE` (30), `HEADLESS_QUEUE` (default); R2 credentials = the existing `R2_*` (scoped server token, IPv4 forced);
`SPORTVATAR_ACCOUNT_PROOF_ORIGINS` must contain the frontend origin. WalletConnect (Flow Wallet mobile): the Reown project's domain
allowlist must contain the frontend host (`https://*.sportvatar.com` covers next-testnet; the apex needs its own entry).

## 9. Rollback

Remove the Worker's custom domain/route (the classic site keeps serving on the same origin the whole time; prod: delete the
`sportvatar.com/*` route);
`HEADLESS_ENABLED=false` + `config:cache` stops jobs/schedules; without `window.HEADLESS` the JS behaves exactly as before.
