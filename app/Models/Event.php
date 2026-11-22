<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'flow_metadata' => AsCollection::class,
        'synced' => 'boolean',
        'graffle' => 'boolean',
        'event_date' => 'datetime'
    ];
    protected $guarded = [];
}
