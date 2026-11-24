<?php

namespace App\Services\Render;

use App\Models\Sportvatar;
use App\Models\SportvatarTemplate;

/**
 * Which items carry CSS @keyframes / SMIL (their PNG/JPG should come from the browser renderer;
 * the server render is the instant fallback). SQL LIKE on the stored SVGs - no composing.
 */
class AnimatedItems
{
    private const LIKE = ['%@keyframes%', '%@-webkit-keyframes%', '%<animate%', '%<set %'];

    /** @param int[]|null $only restrict to these flow ids @return int[] */
    public static function ids(string $type, ?array $only = null): array
    {
        $query = ($type === 'template' ? SportvatarTemplate::query() : Sportvatar::query())->whereNotNull('flow_id');
        if ($only !== null) {
            $query->whereIn('flow_id', $only ?: [0]);
        }
        $query->where(function ($q) {
            foreach (self::LIKE as $like) {
                $q->orWhere('svg', 'like', $like);
            }
        });

        return $query->orderBy('flow_id')->pluck('flow_id')->map(fn ($v) => (int) $v)->all();
    }
}
