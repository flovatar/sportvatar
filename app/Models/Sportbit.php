<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Sportbit extends Model
{
    use HasFactory;

    protected $casts = [
        'flow_metadata' => AsCollection::class,
        'destroyed' => 'boolean',
        'packed' => 'boolean'
    ];
    protected $guarded = [];
    protected $appends = ['rarity'];

    public function saleOffers()
    {
        return $this->morphMany(SaleOffer::class, 'item');
    }

    public function sales()
    {
        return $this->morphMany(Sale::class, 'item');
    }

    public function sportvatarTemplate()
    {
        return $this->belongsTo(SportvatarTemplate::class);
    }



    public function scopeReadyForPack($query){
        return $query->whereFlowAddress(config('services.flow.address'))
            ->whereNotNull('flow_id')
            ->whereNotNull('flow_address')
            ->whereDestroyed(0)
            ->wherePacked(0);
    }


    public function scopeWhereComponent($query){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2) {
            return $query2->where('name','!=', 'Sport Flame')->where('layer','!=', 0);
        });
    }
    public function scopeWhereFlame($query){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2)  {
            return $query2->whereName('Sport Flame')->whereLayer(0);
        });
    }
    public function scopeWhereLayer($query, $layer){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2) use ($layer) {
            return $query2->whereLayer($layer);
        });
    }
    public function scopeWhereNumber($query){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2) {
            return $query2->whereLayer(13);
        });
    }
    public function scopeWhereName($query, $name){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2) use ($name) {
            return $query2->whereName($name);
        });
    }
    public function scopeWhereRarity($query, $rarity){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2) use ($rarity) {
            return $query2->where('name','!=', 'Sport Flame')->where('layer','!=', 0)->where('layer', '!=', 13)->whereRarity($rarity);
        });
    }
    public function scopeWhereFlameRarity($query, $rarity){
        return $query->whereHas('sportvatarTemplate', function(Builder $query2) use ($rarity) {
            return $query2->whereName('Sport Flame')->whereLayer(0)->whereRarity($rarity);
        });
    }

    public function getRarityAttribute()
    {
        return $this->sportvatarTemplate ? $this->sportvatarTemplate->rarity : '';
    }

}
