<?php

namespace App\Filament\Resources\SaleOffers;

use App\Models\SaleOffer;
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

class SaleOfferResource extends Resource
{
    protected static ?string $model = SaleOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?string $navigationLabel = 'Listings';

    protected static ?string $pluralModelLabel = 'Listings';

    // Blockchain-derived data: the indexer is authoritative, so the admin is read-only.
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                TextEntry::make('id')->placeholder('-'),
                TextEntry::make('item_type')->placeholder('-'),
                TextEntry::make('flow_id')->placeholder('-'),
                TextEntry::make('price')->placeholder('-'),
                TextEntry::make('flow_address')->placeholder('-')->copyable(),
                TextEntry::make('listing_id')->placeholder('-'),
                IconEntry::make('is_dapper')->boolean(),
                TextEntry::make('created_at')->dateTime()->placeholder('-'),
                TextEntry::make('flow_metadata')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->columnSpanFull()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('item_type'),
                TextColumn::make('flow_id')->numeric(thousandsSeparator: '')->sortable()->searchable(),
                TextColumn::make('price')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('flow_address')->copyable()->fontFamily('mono')->searchable(),
                TextColumn::make('listing_id')->searchable(),
                IconColumn::make('is_dapper')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('item_type')->options(['App\\Models\\Sportvatar' => 'Sportvatar', 'App\\Models\\Sportbit' => 'Sportbit'])
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaleOffers::route('/'),
            'view' => Pages\ViewSaleOffer::route('/{record}'),
        ];
    }
}
