<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FilteredController;
use App\Models\Sportvatar;
use Cache;
use DB;

class SportvatarsController extends FilteredController
{
    public function all()
    {
        return Cache::remember('db-sportvatar-get-all', now()->addSeconds(60), function() {
            return Sportvatar::select(
            'flow_id',
            'flow_address',
            'flow_metadata->creatorAddress as creatorAddress',
            'flow_metadata->stats->power as power',
            'flow_metadata->stats->speed as speed',
            'flow_metadata->stats->endurance as endurance',
            'flow_metadata->stats->technique as technique',
            'flow_metadata->stats->mental strength as mentalStrength',
            'flow_metadata->layers->2->value as body',
            'flow_metadata->layers->3->value as clothing',
            'flow_metadata->layers->4->value as nose',
            'flow_metadata->layers->5->value as mouth',
            'flow_metadata->layers->6->value as facialHair',
            'flow_metadata->layers->7->value as hair',
            'flow_metadata->layers->8->value as eyes',
            'flow_metadata->layers->9->value as eyeglasses',
            'flow_metadata->layers->10->value as hat',
            'flow_metadata->layers->11->value as shoes',
            'flow_metadata->layers->12->value as accessory',
            'flow_metadata->layers->13->value as number',
            'flow_metadata->series as series',
            'flow_metadata->combination as combination',
            'rarity',
            'ability',
            'created_at',
            'updated_at')
            ->orderBy('flow_id')
            ->get();
        });
    }
    public function byId($id)
    {
        return Sportvatar::whereFlowId($id)->first();

    }
    public function search()
    {

        $sportvatars = Cache::remember('db-sportvatar-search-'.$this->paramString(), now()->addSeconds(30), function() {
            return $this->getFilteredSportvatars(request());
        });

        return $sportvatars;
    }
}
