<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * GET /api/packs/pending/{address}: count + claims flag + the wallet types the claim is offered to
 * (SPORTVATAR_PACK_CLAIMS_WALLETS). Read-only against the configured database.
 */
class PendingPackClaimsTest extends TestCase
{
    public function test_reports_the_claim_wallets_and_flag(): void
    {
        config(['sportvatar.packClaimsEnabled' => true, 'sportvatar.packClaimWallets' => ['flow']]);
        $this->getJson('/api/packs/pending/0x0000000000000001')
            ->assertOk()
            ->assertExactJson(['count' => 0, 'claimsOpen' => true, 'claimWallets' => ['flow']])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_paused_claims_report_closed(): void
    {
        config(['sportvatar.packClaimsEnabled' => false]);
        $this->getJson('/api/packs/pending/0x0000000000000001')->assertOk()->assertJson(['claimsOpen' => false]);
    }

}
