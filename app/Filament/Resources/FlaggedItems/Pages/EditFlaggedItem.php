<?php

namespace App\Filament\Resources\FlaggedItems\Pages;

use App\Filament\Resources\FlaggedItems\FlaggedItemResource;
use Filament\Resources\Pages\EditRecord;

class EditFlaggedItem extends EditRecord
{
    protected static string $resource = FlaggedItemResource::class;
}
