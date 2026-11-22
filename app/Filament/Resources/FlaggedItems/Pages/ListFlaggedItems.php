<?php

namespace App\Filament\Resources\FlaggedItems\Pages;

use App\Filament\Resources\FlaggedItems\FlaggedItemResource;
use Filament\Resources\Pages\ListRecords;

class ListFlaggedItems extends ListRecords
{
    protected static string $resource = FlaggedItemResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
