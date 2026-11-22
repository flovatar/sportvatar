<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportvatarTemplate extends Model
{
    use HasFactory;

    protected $casts = [
        'flow_metadata' => AsCollection::class
    ];
    protected $guarded = [];

    public function sportbits()
    {
        return $this->hasMany(Sportbit::class);
    }

    public function sportvatarSeries()
    {
        return $this->belongsTo(SportvatarSeries::class);
    }

    public function getFullSvgAttribute(){
        if(!$this->svg || !$this->sportvatar_series_id){
            return '';
        }
        $series = SportvatarSeries::whereFlowId($this->sportvatar_series_id)->first();

        return $series ? $series->svg_prefix.$this->svg.$series->svg_suffix : '';
    }
}
