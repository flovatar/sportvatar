<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportvatarSeries extends Model
{
    use HasFactory;

    protected $casts = [
        'flow_metadata' => AsCollection::class,
        'colors' => AsCollection::class,
        'layers' => AsCollection::class
    ];
    protected $guarded = [];

    public function sportvatarTemplates()
    {
        return $this->hasMany(SportvatarTemplate::class);
    }

    public function sportvatar()
    {
        return $this->hasMany(Sportvatar::class);
    }
}
