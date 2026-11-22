<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemeTemplate extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function memes()
    {
        return $this->hasMany(Meme::class);
    }
}
