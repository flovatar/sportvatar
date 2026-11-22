<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackVoucher extends Model
{
    protected $guarded = [];
    protected $hidden = ['code_hash'];
    protected $casts = ['claimed_at' => 'datetime'];

    public function scopeActiveReservation($query)
    {
        return $query->whereStatus('reserved')->where('authorization_expiry', '>', time());
    }

    // Called by the event processor when a pack is deposited to a wallet (SportvatarPack.Deposit after Claimed).
    public static function markClaimed($packFlowId, $toAddress): void
    {
        static::where('pack_flow_id', $packFlowId)->whereIn('status', ['reserved', 'issued'])
            ->where('redeemed_by', strtolower($toAddress))
            ->update(['status' => 'claimed', 'claimed_at' => now()]);
    }
}
