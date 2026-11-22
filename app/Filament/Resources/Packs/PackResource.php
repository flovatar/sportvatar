<?php

namespace App\Filament\Resources\Packs;

use App\Models\Pack;
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

class PackResource extends Resource
{
    protected static ?string $model = Pack::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'NFTs';

    protected static ?string $navigationLabel = 'Packs';

    protected static ?string $pluralModelLabel = 'Packs';

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
                TextEntry::make('flame_count')->placeholder('-'),
                TextEntry::make('flow_address')->placeholder('-')->copyable(),
                IconEntry::make('opened')->boolean(),
                TextEntry::make('user_id')->placeholder('-'),
                TextEntry::make('assigned_at')->dateTime()->placeholder('-'),
                TextEntry::make('flow_metadata')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->columnSpanFull()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('flow_id', 'desc')
            ->columns([
                TextColumn::make('flow_id')->numeric(thousandsSeparator: '')->sortable()->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('flame_count')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('flow_address')->copyable()->fontFamily('mono')->searchable(),
                IconColumn::make('opened')->boolean(),
                TextColumn::make('user_id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('assigned_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('opened')
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPacks::route('/'),
            'view' => Pages\ViewPack::route('/{record}'),
        ];
    }
}
