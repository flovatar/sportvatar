<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Latest static render of an item on R2 (see App\Services\Render\ItemRenderer). */
class RenderedImage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variants' => 'array',
        'rendered_at' => 'datetime',
    ];

    public function hasVariant(string $variant): bool
    {
        return in_array($variant, $this->variants ?? [], true);
    }

    public function url(string $variant = 'full.png'): ?string
    {
        if ($this->error && ! $this->rendered_at) {
            return null;
        }
        if (! $this->rendered_at || ! $this->hasVariant($variant)) {
            return null;
        }

        return config('render.public_url').'/'.$this->path.'/'.$variant;
    }
}
