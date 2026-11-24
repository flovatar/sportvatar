<?php

namespace Tests\Feature;

use App\Http\Middleware\ThrottleRequestsPerRoute;
use App\Jobs\RegenerateStaticItem;
use App\Models\SaleOffer;
use App\Models\Sportbit;
use App\Models\Sportvatar;
use App\Support\Headless;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Headless frontend contract: API v2 (public cache headers, CORS, JSON shapes, no svg/secrets), auth endpoints
 * (private), static build (per-item page with snapshot + canonical + og, shells without CSRF token) and debounced
 * regeneration (model observers + indexer hook). In-memory SQLite schema with the columns the code reads.
 */
class HeadlessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
            'cache.default' => 'array',
            'headless.frontend_url' => 'https://next-testnet.sportvatar.com',
            'headless.enabled' => false,
            'render.legacy_fallback' => false,
            'sportvatar.blockedAddresses' => ['0xd573a23406d5bec2'],
            'sportvatar.series' => 2,
            'services.flow.network' => 'testnet',
        ]);
        DB::purge();
        $this->withoutMiddleware(ThrottleRequestsPerRoute::class);
        $this->schema();
        $this->seedRows();
    }

    private function schema(): void
    {
        Schema::create('sportvatars', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('mint')->nullable();
            $t->string('name')->nullable();
            $t->integer('sportvatar_series_id')->nullable();
            $t->longText('svg')->nullable();
            $t->unsignedBigInteger('flow_id')->nullable();
            $t->string('rarity')->nullable();
            $t->integer('ability')->nullable();
            $t->string('flow_address')->nullable();
            $t->json('flow_metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('sportbits', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sportvatar_template_id')->nullable();
            $t->unsignedBigInteger('flow_id')->nullable();
            $t->string('flow_address')->nullable();
            $t->json('flow_metadata')->nullable();
            $t->integer('mint')->default(0);
            $t->boolean('destroyed')->default(false);
            $t->boolean('packed')->default(false);
            $t->timestamps();
        });
        Schema::create('sportvatar_templates', function (Blueprint $t) {
            $t->increments('id');
            foreach (['name', 'rarity', 'sport', 'flow_address'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('description')->nullable();
            $t->integer('layer')->default(0);
            $t->longText('svg')->nullable();
            $t->integer('max_mintable')->default(0);
            $t->integer('minted')->default(0);
            $t->integer('sportvatar_series_id')->nullable();
            $t->unsignedBigInteger('flow_id')->nullable();
            $t->json('flow_metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('sportvatar_series', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->text('description')->nullable();
            $t->longText('svg_prefix')->nullable();
            $t->longText('svg_suffix')->nullable();
            $t->json('layers')->nullable();
            $t->json('colors')->nullable();
            $t->integer('max_mintable')->default(0);
            $t->integer('minted')->default(0);
            $t->unsignedBigInteger('flow_id')->nullable();
            $t->string('flow_address')->nullable();
            $t->json('flow_metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('sale_offers', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('item_id');
            $t->string('item_type');
            $t->unsignedBigInteger('flow_id')->nullable();
            $t->string('flow_address')->nullable();
            $t->json('flow_metadata')->nullable();
            $t->decimal('price', 12, 2)->default(0);
            $t->string('currency', 16)->nullable();
            $t->boolean('is_completed')->default(false);
            $t->boolean('is_dapper')->default(false);
            $t->unsignedBigInteger('listing_id')->nullable();
            $t->timestamps();
        });
        Schema::create('sales', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('item_id');
            $t->string('item_type');
            $t->decimal('price', 12, 2)->default(0);
            $t->string('currency', 16)->nullable();
            $t->string('transaction_id')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->string('flow_buyer_address')->nullable();
            $t->string('flow_seller_address')->nullable();
            $t->boolean('is_dapper')->default(false);
            $t->timestamps();
        });
        Schema::create('rendered_images', function (Blueprint $t) {
            $t->increments('id');
            $t->string('item_type');
            $t->unsignedBigInteger('flow_id');
            $t->string('hash')->nullable();
            $t->string('path')->nullable();
            $t->string('source')->nullable();
            $t->boolean('animated')->default(false);
            $t->json('variants')->nullable();
            $t->integer('bytes')->nullable();
            $t->integer('render_ms')->nullable();
            $t->json('warnings')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('rendered_at')->nullable();
            $t->timestamps();
        });
        Schema::create('flagged_items', function (Blueprint $t) {
            $t->increments('id');
            $t->string('item_type');
            $t->unsignedBigInteger('flow_id');
            $t->string('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('packs', function (Blueprint $t) {
            $t->increments('id');
            $t->string('secret')->nullable();
            $t->string('signature')->nullable();
            $t->unsignedBigInteger('flow_id')->nullable();
            $t->integer('flame_count')->nullable();
            $t->integer('sportvatar_series_id')->nullable();
            $t->string('name')->nullable();
            $t->string('flow_address')->nullable();
            $t->json('flow_metadata')->nullable();
            $t->boolean('opened')->default(false);
            $t->integer('user_id')->nullable();
            $t->timestamp('assigned_at')->nullable();
            $t->timestamps();
        });
    }

    private function seedRows(): void
    {
        $now = now();
        DB::table('sportvatar_series')->insert(['id' => 2, 'name' => 'Sportvatar', 'flow_id' => 2, 'svg_prefix' => '<svg>', 'svg_suffix' => '</svg>', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sportvatar_templates')->insert([
            ['id' => 12, 'name' => 'Sport Flame', 'rarity' => 'common', 'layer' => 0, 'svg' => '<g id="flame">TPLSVG</g>', 'flow_id' => 12, 'sportvatar_series_id' => 2, 'max_mintable' => 100, 'flow_metadata' => '{"big":"META"}', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 54, 'name' => 'Cap Black', 'rarity' => 'common', 'layer' => 10, 'svg' => '<g id="cap">TPLSVG</g>', 'flow_id' => 54, 'sportvatar_series_id' => 2, 'max_mintable' => 100, 'flow_metadata' => '{"big":"META"}', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $meta = ['id' => '7', 'mint' => '7', 'name' => '', 'svg' => '<svg>BIGSVG</svg>', 'rarity' => 'common', 'series' => '2', 'stats' => ['power' => '3'],
            'layers' => ['2' => ['type' => 'UInt64', 'value' => '14']], 'combination' => 'S2-L2_14', 'creatorAddress' => '0x2538515561c0c431', 'metadata' => []];
        DB::table('sportvatars')->insert([
            'mint' => 7, 'name' => '', 'sportvatar_series_id' => 2, 'svg' => '<svg>BIGSVG</svg>', 'flow_id' => 7, 'rarity' => 'common', 'ability' => 17,
            'flow_address' => '0x2538515561c0c431', 'flow_metadata' => json_encode($meta), 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('sportbits')->insert(['sportvatar_template_id' => 54, 'flow_id' => 32, 'flow_address' => '0x336c3466f8c4ce50', 'flow_metadata' => '{"id":"32","mint":"1","templateId":"54"}', 'mint' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sale_offers')->insert([
            'item_id' => 1, 'item_type' => Sportvatar::class, 'flow_id' => 7, 'flow_address' => '0x2538515561c0c431', 'flow_metadata' => json_encode($meta),
            'price' => 2.5, 'currency' => 'FLOW', 'is_completed' => false, 'is_dapper' => false, 'listing_id' => 123456, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('sales')->insert([
            'item_id' => 1, 'item_type' => Sportbit::class, 'price' => 1.2, 'currency' => 'FLOW', 'transaction_id' => 'abc', 'flow_buyer_address' => '0x336c3466f8c4ce50',
            'flow_seller_address' => '0xdeeb3384569f740a', 'is_dapper' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('packs')->insert(['secret' => 'NEVER-SHOWN', 'signature' => 'SIG-NEVER', 'flow_id' => 5, 'flame_count' => 1, 'sportvatar_series_id' => 2, 'name' => 'Genesis Free Pack', 'opened' => false, 'created_at' => $now, 'updated_at' => $now]);
    }

    private function assertPublic($r): void
    {
        $r->assertOk();
        $cc = (string) $r->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cc);
        $this->assertStringContainsString('s-maxage=60', $cc);
        $this->assertStringContainsString('stale-while-revalidate=300', $cc);
        $this->assertSame('*', $r->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame([], $r->headers->getCookies());
    }

    public function test_sportvatar_snapshot_is_public_lean_and_canonical(): void
    {
        $r = $this->get('/api/v2/sportvatars/7');
        $this->assertPublic($r);
        $r->assertJsonPath('id', 7)
            ->assertJsonPath('owner', '0x2538515561c0c431')
            ->assertJsonPath('url', 'https://next-testnet.sportvatar.com/sportvatars/7')
            ->assertJsonPath('listing.price', 2.5)
            ->assertJsonPath('listing.currency', 'FLOW')
            ->assertJsonPath('listing.listing_id', '123456')
            ->assertJsonPath('rarity', 'common')
            ->assertJsonPath('layers.2', 14)
            ->assertJsonPath('creator', '0x2538515561c0c431');
        $this->assertStringNotContainsString('BIGSVG', $r->getContent());
    }

    public function test_sportbit_snapshot_has_template_and_sales(): void
    {
        $r = $this->get('/api/v2/sportbits/32');
        $this->assertPublic($r);
        $r->assertJsonPath('template.name', 'Cap Black')->assertJsonPath('template.id', 54)
            ->assertJsonPath('sales.0.price', 1.2)->assertJsonPath('owner', '0x336c3466f8c4ce50');
        $this->assertStringNotContainsString('TPLSVG', $r->getContent());
        $this->get('/api/v2/components/32')->assertOk()->assertJsonPath('id', 32);
    }

    public function test_unknown_item_is_json_404(): void
    {
        $r = $this->get('/api/v2/sportvatars/999');
        $r->assertNotFound();
        $this->assertStringContainsString('application/json', (string) $r->headers->get('Content-Type'));
        $this->assertNotContains('Set-Cookie', array_keys($r->headers->all()));
    }

    public function test_lists_keep_legacy_paginator_shape_without_svg(): void
    {
        $m = $this->get('/api/v2/marketplace?sortby=datenew');
        $this->assertPublic($m);
        $m->assertJsonStructure(['current_page', 'data' => [['flow_id', 'flow_address', 'price', 'currency', 'listing_id', 'thumb', 'item' => ['flow_id', 'flow_metadata']]], 'last_page', 'total'])
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.item.flow_metadata.rarity', 'common');
        $this->assertStringNotContainsString('BIGSVG', $m->getContent());

        $this->assertSame(0, $this->get('/api/v2/marketplace?currency=DUC')->json('total'));

        $l = $this->get('/api/v2/locker-room?sortby=datenew');
        $this->assertPublic($l);
        $l->assertJsonPath('data.0.flow_id', 7);
        $this->assertStringNotContainsString('BIGSVG', $l->getContent());

        $t = $this->get('/api/v2/transactions?address=0x336c3466f8c4ce50');
        $this->assertPublic($t);
        $t->assertJsonPath('total', 1)->assertJsonPath('data.0.item.sportvatar_template_id', 54);
        $this->assertStringNotContainsString('TPLSVG', $t->getContent());
    }

    public function test_templates_meta_stats_and_type(): void
    {
        $tpl = $this->get('/api/v2/templates?set=builder');
        $this->assertPublic($tpl);
        $tpl->assertJsonCount(2);
        $this->assertStringNotContainsString('TPLSVG', $tpl->getContent());
        $this->assertStringNotContainsString('META', $tpl->getContent());

        $this->get('/api/v2/templates/54')->assertOk()->assertJsonPath('template.name', 'Cap Black')->assertJsonPath('minted', 1);
        $this->get('/api/v2/templates/9999')->assertNotFound();
        $this->get('/api/v2/meta')->assertOk()->assertJsonPath('series', 2)->assertJsonStructure(['templates' => ['all' => ['version', 'script'], 'series2']]);
        $this->get('/api/v2/marketplace/stats')->assertOk()->assertJsonPath('listings', 1)->assertJsonPath('flow.count', 1);
        $this->get('/api/v2/rankings')->assertOk()->assertJsonPath('holders.0.address', '0x2538515561c0c431');
    }

    public function test_template_data_script_is_versioned_and_immutable(): void
    {
        $v = \App\Http\Controllers\Controller::templatesVersion('series2');
        $r = $this->get('/templates/data/series2.js?v='.$v);
        $r->assertOk();
        $this->assertStringStartsWith('window.templates = ', $r->getContent());
        $this->assertStringContainsString('immutable', (string) $r->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('META', $r->getContent());
        $this->get('/templates/data/nope.js')->assertNotFound();
    }

    public function test_packs_never_expose_secrets(): void
    {
        $r = $this->get('/api/v2/packs');
        $this->assertPublic($r);
        $r->assertJsonPath('0.unopened', 1)->assertJsonPath('0.name', 'Genesis Free Pack');
        $this->assertStringNotContainsString('NEVER-SHOWN', $r->getContent());
        $this->assertStringNotContainsString('SIG-NEVER', $r->getContent());
    }

    public function test_auth_endpoints_are_private(): void
    {
        $me = $this->getJson('/api/v2/auth/me');
        $me->assertOk()->assertJson(['authenticated' => false, 'address' => null]);
        $this->assertStringContainsString('no-store', (string) $me->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $me->headers->get('Cache-Control'));

        $nonce = $this->getJson('/api/v2/auth/nonce');
        $nonce->assertOk()->assertJsonStructure(['nonce', 'message', 'authenticated']);
        $this->assertStringContainsString('no-store', (string) $nonce->headers->get('Cache-Control'));

        // an unverifiable proof never logs in (CSRF is enforced outside tests like POST /login)
        $login = $this->postJson('/api/v2/auth/login', ['address' => '0x2538515561c0c431', 'proofType' => 'account-proof', 'nonce' => str_repeat('0', 64), 'signatures' => []]);
        $login->assertOk()->assertJson(['result' => false, 'address' => null]);
        $this->assertStringContainsString('no-store', (string) $login->headers->get('Cache-Control'));
    }

    public function test_static_build_writes_item_page_with_snapshot_canonical_and_og(): void
    {
        $out = sys_get_temp_dir().'/headless-test-'.uniqid();
        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('headless:build', ['--items' => true, '--type' => 'sportvatar', '--out' => $out, '--quiet-puts' => true]), \Illuminate\Support\Facades\Artisan::output());
        $file = $out.'/'.Headless::prefix().'sportvatars/7/index.html';
        $this->assertFileExists($file);
        $this->assertStringStartsWith('testnet/', Headless::prefix());
        $html = file_get_contents($file);
        $this->assertStringContainsString('<link rel="canonical" href="https://next-testnet.sportvatar.com/sportvatars/7" />', $html);
        $this->assertStringContainsString('og:url" content="https://next-testnet.sportvatar.com/sportvatars/7"', $html);
        $this->assertStringContainsString('window.HEADLESS = ', $html);
        $this->assertStringContainsString('"owner":"0x2538515561c0c431"', $html);
        $this->assertStringContainsString("sportvatarData('0x2538515561c0c431', 7", $html);
        $this->assertStringContainsString('/templates/data/all.js?v=', $html);
        $this->assertStringNotContainsString('BIGSVG', $html);
        $this->assertStringNotContainsString('csrf-token', $html);
        // second run: unchanged, nothing rewritten
        $this->artisan('headless:build', ['--items' => true, '--type' => 'sportvatar', '--out' => $out])->expectsOutputToContain('0 uploaded')->assertExitCode(0);
    }

    public function test_static_build_writes_shells_without_session_data(): void
    {
        $out = sys_get_temp_dir().'/headless-test-'.uniqid();
        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('headless:build', ['--shells' => true, '--page' => ['collection', '404', 'privacy', 'home-it'], '--out' => $out, '--quiet-puts' => true]), \Illuminate\Support\Facades\Artisan::output());
        $shell = file_get_contents($out.'/'.Headless::prefix().'_shells/collection.html');
        $this->assertStringContainsString('window.HEADLESS_ROUTE.address', $shell);
        $this->assertStringContainsString('"shell":true', $shell);
        $this->assertStringNotContainsString('0x0000000000000000', $shell);
        $this->assertFileExists($out.'/'.Headless::prefix().'404.html');
        $this->assertStringContainsString('lang="it"', file_get_contents($out.'/'.Headless::prefix().'it/index.html'));
    }

    public function test_listing_change_schedules_one_debounced_regeneration(): void
    {
        config(['headless.enabled' => true, 'headless.debounce' => 30]);
        Queue::fake();
        $offer = SaleOffer::first();
        $offer->price = 20;
        $offer->save();
        $offer->price = 21;
        $offer->save();
        Sportvatar::where('flow_id', 7)->first()->update(['flow_address' => '0x336c3466f8c4ce50']);
        Queue::assertPushed(RegenerateStaticItem::class, 1);
        Queue::assertPushed(RegenerateStaticItem::class, fn ($job) => $job->type === 'sportvatar' && $job->flowId === 7);
    }

    public function test_regeneration_is_off_when_disabled(): void
    {
        Queue::fake();
        Sportvatar::where('flow_id', 7)->first()->update(['flow_address' => '0x336c3466f8c4ce50']);
        Queue::assertNothingPushed();
    }

    public function test_indexer_events_map_to_item_pages(): void
    {
        config(['headless.enabled' => true]);
        Queue::fake();
        \App\Support\HeadlessRegen::fromEvent('A.c80df59e5354ca8a.Sportvatar.Deposit', ['id' => 3, 'to' => '0x1']);
        \App\Support\HeadlessRegen::fromEvent('A.2d55b98eb200daef.NFTStorefrontV2.ListingAvailable', ['nftType' => ['typeID' => 'A.c80df59e5354ca8a.Sportvatar.NFT'], 'nftID' => 5]);
        \App\Support\HeadlessRegen::fromEvent('A.2d55b98eb200daef.NFTStorefrontV2.Listing.ResourceDestroyed', ['nftType' => 'A.c80df59e5354ca8a.Sportvatar.NFT', 'nftID' => '6']);
        \App\Support\HeadlessRegen::fromEvent('A.2d55b98eb200daef.NFTStorefrontV2.ListingAvailable', ['nftType' => ['typeID' => 'A.c80df59e5354ca8a.Sportbit.NFT'], 'nftID' => 9]);
        \App\Support\HeadlessRegen::fromEvent('A.c80df59e5354ca8a.Sportbit.Deposit', ['id' => 5]);
        Queue::assertPushed(RegenerateStaticItem::class, 3);
        foreach ([3, 5, 6] as $id) {
            Queue::assertPushed(RegenerateStaticItem::class, fn ($j) => $j->type === 'sportvatar' && $j->flowId === $id);
        }
    }
}
