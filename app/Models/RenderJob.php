<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Browser render job (see App\Services\Render\RenderJobs). */
class RenderJob extends Model
{
    protected $guarded = [];

    protected $casts = [
        'available_at' => 'datetime',
        'lease_until' => 'datetime',
        'done_at' => 'datetime',
        'requeue' => 'boolean',
    ];
}
