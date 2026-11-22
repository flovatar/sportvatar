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

    public function item()
    {
        return $this->morphTo();
    }

}
