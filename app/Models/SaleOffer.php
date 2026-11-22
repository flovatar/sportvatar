<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOffer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'flow_metadata' => AsCollection::class
    ];

    public function item()
    {
        return $this->morphTo();
    }
    public function sportvatar()
    {
        return $this->belongsTo(Sportvatar::class, 'sportvatar_id')
            ->whereItemType(Sportvatar::class);
    }
    public function component()
    {
        return $this->belongsTo(Sportbit::class, 'sportvatar_id')
            ->whereItemType(Sportbit::class);
    }

    public function scopeIsSportvatar($query){
        return $query->whereItemType('App\Models\Sportvatar');
    }
    /**
     * Hide listings of blocklisted sellers and flagged NFTs (2026-11-16 incident).
     */
    public function scopeNotBlocked($query){
        $addresses = \App\Support\Blocklist::addresses();
        if(count($addresses) > 0){
            $query->whereNotIn('sale_offers.flow_address', $addresses);
        }
        return $query->whereNotExists(function ($q) {
            $q->selectRaw('1')->from('flagged_items')
                ->whereColumn('flagged_items.item_type', 'sale_offers.item_type')
                ->whereColumn('flagged_items.flow_id', 'sale_offers.flow_id');
        });
    }

    public function scopeIsComponent($query){
        return $query->whereItemType('App\Models\Sportbit');
    }
}
