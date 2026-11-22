<?php

namespace App\Filament\Resources\FlaggedItems;

use App\Models\FlaggedItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class FlaggedItemResource extends Resource
{
    protected static ?string $model = FlaggedItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Incident';

    protected static ?string $navigationLabel = 'Flagged items';

    protected static ?string $pluralModelLabel = 'Flagged items';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('item_type')->options(['App\\Models\\Sportvatar' => 'Sportvatar', 'App\\Models\\Sportbit' => 'Sportbit', 'App\\Models\\Pack' => 'Pack'])->required(),
            TextInput::make('flow_id')->numeric()->required(),
            TextInput::make('reason')->maxLength(255)->default('manual'),
            TextInput::make('address')->maxLength(18)->regex('/^0x[a-f0-9]{16}$/'),
            TextInput::make('transaction_id')->maxLength(64),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                TextEntry::make('item_type')->placeholder('-'),
                TextEntry::make('flow_id')->placeholder('-'),
                TextEntry::make('reason')->placeholder('-'),
                TextEntry::make('address')->placeholder('-')->copyable(),
                TextEntry::make('transaction_id')->placeholder('-')->copyable(),
                TextEntry::make('created_at')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('item_type'),
                TextColumn::make('flow_id')->numeric(thousandsSeparator: '')->sortable()->searchable(),
                TextColumn::make('reason')->searchable(),
                TextColumn::make('address')->copyable()->fontFamily('mono')->searchable(),
                TextColumn::make('transaction_id')->searchable()->limit(16)->copyable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('item_type')->options(['App\\Models\\Sportvatar' => 'Sportvatar', 'App\\Models\\Sportbit' => 'Sportbit', 'App\\Models\\Pack' => 'Pack'])
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlaggedItems::route('/'),
            'create' => Pages\CreateFlaggedItem::route('/create'),
            'view' => Pages\ViewFlaggedItem::route('/{record}'),
            'edit' => Pages\EditFlaggedItem::route('/{record}/edit'),
        ];
    }
}
