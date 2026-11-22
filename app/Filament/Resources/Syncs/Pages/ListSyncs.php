<?php

namespace App\Filament\Resources\Syncs\Pages;

use App\Filament\Resources\Syncs\SyncResource;
use Filament\Resources\Pages\ListRecords;

class ListSyncs extends ListRecords
{
    protected static string $resource = SyncResource::class;
}
