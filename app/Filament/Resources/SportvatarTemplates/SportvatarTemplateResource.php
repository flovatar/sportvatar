<?php

namespace App\Filament\Resources\SportvatarTemplates;

use App\Models\SportvatarTemplate;
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

class SportvatarTemplateResource extends Resource
{
    protected static ?string $model = SportvatarTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $pluralModelLabel = 'Templates';

    // Blockchain-derived data: the indexer is authoritative, so the admin is read-only.
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                TextEntry::make('flow_id')->placeholder('-'),
                TextEntry::make('name')->placeholder('-'),
                TextEntry::make('layer')->placeholder('-'),
                TextEntry::make('rarity')->placeholder('-'),
                TextEntry::make('max_mintable')->placeholder('-'),
                TextEntry::make('minted')->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('flow_id', 'desc')
            ->columns([
                TextColumn::make('flow_id')->numeric(thousandsSeparator: '')->sortable()->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('layer')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('rarity')->badge(),
                TextColumn::make('max_mintable')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('minted')->numeric(thousandsSeparator: '')->sortable(),
            ])
            ->filters([
                SelectFilter::make('rarity')->options(['common' => 'common', 'rare' => 'rare', 'epic' => 'epic', 'legendary' => 'legendary'])
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('public')->label('Public page')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->url(fn ($record) => url('/api/image/template/'.$record->flow_id))->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSportvatarTemplates::route('/'),
            'view' => Pages\ViewSportvatarTemplate::route('/{record}'),
        ];
    }
}
