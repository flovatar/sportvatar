<?php

namespace App\Filament\Resources\FlaggedItems\Pages;

use App\Filament\Resources\FlaggedItems\FlaggedItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFlaggedItem extends CreateRecord
{
    protected static string $resource = FlaggedItemResource::class;
}
