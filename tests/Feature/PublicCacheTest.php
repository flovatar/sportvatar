<?php

namespace Tests\Feature;

use App\Http\Middleware\ThrottleRequestsPerRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Edge cache contract (App\Support\PublicCache): anonymous public GETs carry no cookies and a
 * shared-cache header; session requests are private; CSRF comes from /csrf-token; the login nonce
 * lives in an encrypted cookie (no session). No database needed: public pages are stubbed with
 * closures on their real paths.
 */
class PublicCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'array', 'cache.default' => 'array']);
        $this->withoutMiddleware(ThrottleRequestsPerRoute::class);
        Route::middleware('web')->get('hall-of-fame', fn () => response('public page '.app()->getLocale()));
        Route::middleware('web')->post('locker-room/search', fn () => response()->json(['data' => []]));
    }

    private function sessionCookieName(): string
    {
        return config('session.cookie');
    }

    public function test_anonymous_public_get_has_no_cookies_and_public_cache_header(): void
    {
        $r = $this->get('/hall-of-fame');
        $r->assertOk();
        $this->assertSame([], $r->headers->getCookies());
        $cc = (string) $r->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cc);
        $this->assertStringContainsString('s-maxage=60', $cc);
        $this->assertStringContainsString('stale-while-revalidate=300', $cc);
    }

    public function test_anonymous_locale_ignores_browser_language_but_honours_lang_param(): void
    {
        $this->get('/hall-of-fame', ['Accept-Language' => 'it-IT,it;q=0.9'])->assertSee('public page en');
        $this->get('/hall-of-fame?lang=it', ['Accept-Language' => 'en'])->assertSee('public page it');
    }

    public function test_request_with_session_cookie_is_private(): void
    {
        $r = $this->withUnencryptedCookie($this->sessionCookieName(), 'abc')->get('/hall-of-fame');
        $r->assertOk();
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $r->headers->get('Cache-Control'));
    }

    public function test_readonly_search_post_needs_no_csrf_and_sets_no_cookie(): void
    {
        $r = $this->postJson('/locker-room/search', ['page' => 1]);
        $r->assertOk();
        $this->assertSame([], $r->headers->getCookies());
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
    }

    public function test_csrf_token_endpoint_starts_session(): void
    {
        $r = $this->getJson('/csrf-token');
        $r->assertOk()->assertJsonStructure(['token']);
        $names = array_map(fn ($c) => $c->getName(), $r->headers->getCookies());
        $this->assertContains($this->sessionCookieName(), $names);
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
    }

    // (VerifyCsrfToken is skipped while running unit tests; the 419 path is checked on testnet.)
    public function test_login_with_token_rejects_bad_proof(): void
    {
        $token = $this->getJson('/csrf-token')->json('token');
        $r = $this->postJson('/login', [
            'proofType' => 'account-proof',
            'address' => '0x336c3466f8c4ce50',
            'nonce' => str_repeat('a', 64),
            'signatures' => [['keyId' => 0, 'signature' => 'zz']],
        ], ['X-CSRF-TOKEN' => $token]);
        $r->assertOk()->assertJson(['result' => false]);
    }

    public function test_login_nonce_is_sessionless_and_stable_per_browser(): void
    {
        $r = $this->getJson('/login/nonce');
        $r->assertOk()->assertJson(['authenticated' => false, 'address' => null]);
        $nonce = $r->json('nonce');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $nonce);
        $names = array_map(fn ($c) => $c->getName(), $r->headers->getCookies());
        $this->assertNotContains($this->sessionCookieName(), $names);
        $this->assertContains('sportvatar_login', $names);

        $cookie = collect($r->headers->getCookies())->first(fn ($c) => $c->getName() === 'sportvatar_login');
        $again = $this->withCookie('sportvatar_login', $this->decryptCookie($cookie->getValue()))->getJson('/login/nonce');
        $this->assertSame($nonce, $again->json('nonce'));
    }

    private function decryptCookie(string $value): string
    {
        $plain = decrypt($value, false);

        return \Illuminate\Cookie\CookieValuePrefix::remove($plain);
    }
}
