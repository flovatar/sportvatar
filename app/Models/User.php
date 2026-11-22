<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Users are wallets: `email` holds the Flow address (created by the verified wallet login,
 * AuthController::loginFlowAddress). There is no e-mail/password registration.
 */
class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function packs()
    {
        return $this->hasMany(Pack::class);
    }

    /** Flow address of the wallet (stored in `email`). */
    public function getFlowAddressAttribute(): string
    {
        return strtolower((string) $this->email);
    }

    /** Admin = wallet address listed in ADMIN_ADDRESSES (config sportvatar.adminAddresses). */
    public function isAdmin(): bool
    {
        return in_array($this->flow_address, config('sportvatar.adminAddresses', []), true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isAdmin();
    }

    public function getFilamentName(): string
    {
        return (string) $this->email;
    }
}
