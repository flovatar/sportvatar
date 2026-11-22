<?php

namespace App\Filament\Resources\Sales;

use App\Models\Sale;
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

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?string $navigationLabel = 'Sales';

    protected static ?string $pluralModelLabel = 'Sales';

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
                TextEntry::make('item_id')->placeholder('-'),
                TextEntry::make('price')->placeholder('-'),
                TextEntry::make('flow_seller_address')->placeholder('-')->copyable(),
                TextEntry::make('flow_buyer_address')->placeholder('-')->copyable(),
                TextEntry::make('transaction_id')->placeholder('-')->copyable(),
                TextEntry::make('completed_at')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('completed_at', 'desc')
            ->columns([
                TextColumn::make('id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('item_type'),
                TextColumn::make('item_id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('price')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('flow_seller_address')->copyable()->fontFamily('mono')->searchable(),
                TextColumn::make('flow_buyer_address')->copyable()->fontFamily('mono')->searchable(),
                TextColumn::make('transaction_id')->searchable()->limit(16)->copyable(),
                TextColumn::make('completed_at')->dateTime()->sortable(),
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
            'index' => Pages\ListSales::route('/'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}
