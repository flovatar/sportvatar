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
    public function scopeIsComponent($query){
        return $query->whereItemType('App\Models\Sportbit');
    }
}
