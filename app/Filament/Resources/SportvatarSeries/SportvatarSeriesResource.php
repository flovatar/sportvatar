<?php

namespace App\Filament\Resources\SportvatarSeries;

use App\Models\SportvatarSeries;
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

class SportvatarSeriesResource extends Resource
{
    protected static ?string $model = SportvatarSeries::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Series';

    protected static ?string $pluralModelLabel = 'Series';

    // Blockchain-derived data: the indexer is authoritative, so the admin is read-only.
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                TextEntry::make('id')->placeholder('-'),
                TextEntry::make('flow_id')->placeholder('-'),
                TextEntry::make('name')->placeholder('-'),
                TextEntry::make('max_mintable')->placeholder('-'),
                TextEntry::make('minted')->placeholder('-'),
                TextEntry::make('layers')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->columnSpanFull()->placeholder('-'),
                TextEntry::make('colors')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->columnSpanFull()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('flow_id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('max_mintable')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('minted')->numeric(thousandsSeparator: '')->sortable(),
            ])
            ->filters([
                
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSportvatarSeries::route('/'),
            'view' => Pages\ViewSportvatarSeries::route('/{record}'),
        ];
    }
}
