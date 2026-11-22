<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Imagick;

class Sportvatar extends Model
{
    use HasFactory;

    protected $casts = [
        'flow_metadata' => AsCollection::class
    ];
    protected $guarded = [];
    protected $appends = ['full_svg'];
    protected $hidden = ['svg'];

    public function saleOffers()
    {
        return $this->morphMany(SaleOffer::class, 'item');
    }

    public function sales()
    {
        return $this->morphMany(Sale::class, 'item');
    }

    public function sportvatarSeries()
    {
        return $this->belongsTo(SportvatarSeries::class);
    }

    public function getFullSvgAttribute()
    {
        return $this->svg;
    }

}
