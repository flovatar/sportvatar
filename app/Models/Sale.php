<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $casts = [
        'completed_at' => 'datetime'
    ];

    protected $guarded = [];

    /**
     * Hide sales where a blocklisted address (2026-11-16 incident) is the seller or buyer.
     */
    public function scopeNotBlocked($query)
    {
        $addresses = \App\Support\Blocklist::addresses();
        if (count($addresses) > 0) {
            $query->where(function ($q) use ($addresses) {
                $q->whereNull('sales.flow_buyer_address')->orWhereNotIn('sales.flow_buyer_address', $addresses);
            })->where(function ($q) use ($addresses) {
                $q->whereNull('sales.flow_seller_address')->orWhereNotIn('sales.flow_seller_address', $addresses);
            });
        }
        return $query;
    }

    /**
     * Sales paid in USD (DUC, Dapper Wallet). FLOW sales (Flow Wallet) are excluded from the USD stats.
     */
    public function scopeUsd($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('sales.currency')->orWhere('sales.currency', '!=', 'FLOW');
        });
    }

    public function item()
    {
        return $this->morphTo();
    }

}
