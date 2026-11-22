<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Filament admin (/admin): only wallet users listed in ADMIN_ADDRESSES get in.
 * Runs against the configured database (read-only requests).
 */
class AdminPanelTest extends TestCase
{
    private function wallet(string $address): User
    {
        $user = new User(['email' => $address, 'name' => $address]);
        $user->id = 999999;

        return $user;
    }

    public function test_guests_are_sent_to_the_wallet_sign_in_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.wallet-login'));
        $this->get('/admin/login')->assertOk()->assertSee('ADMIN_ADDRESSES');
    }

    public function test_non_admin_wallets_are_refused(): void
    {
        config(['sportvatar.adminAddresses' => ['0x00000000000000aa']]);
        $this->actingAs($this->wallet('0x00000000000000bb'))->get('/admin')->assertForbidden();
    }

    public function test_admin_wallet_can_open_every_resource(): void
    {
        config(['sportvatar.adminAddresses' => ['0x00000000000000aa']]);
        $admin = $this->wallet('0x00000000000000AA');
        foreach (['', '/users', '/sportvatars', '/sportbits', '/packs', '/sportvatar-series', '/sportvatar-templates',
            '/sales', '/sale-offers', '/events', '/syncs', '/flagged-items', '/flagged-items/create'] as $path) {
            $this->actingAs($admin)->get('/admin'.$path)->assertOk();
        }
    }

    public function test_chain_data_is_read_only(): void
    {
        config(['sportvatar.adminAddresses' => ['0x00000000000000aa']]);
        $this->actingAs($this->wallet('0x00000000000000aa'))->get('/admin/sportvatars/create')->assertNotFound();
    }
}
