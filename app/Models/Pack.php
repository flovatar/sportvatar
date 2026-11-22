<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pack extends Model
{
    use HasFactory;

    protected $casts = [
        'flow_metadata' => AsCollection::class,
        'assigned_at' => 'datetime',
    ];
    protected $guarded = [];


    public function scopeReadyToSell($query){
        return $query->whereNotNull('signature')->whereNotNull('flow_id')->whereFlowAddress(config('services.flow.address'));
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
