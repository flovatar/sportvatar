<?php

namespace App\Filament\Resources\Events;

use App\Models\Event;
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

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Indexer';

    protected static ?string $navigationLabel = 'Chain events';

    protected static ?string $pluralModelLabel = 'Chain events';

    // Blockchain-derived data: the indexer is authoritative, so the admin is read-only.
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                TextEntry::make('id')->placeholder('-'),
                TextEntry::make('block_id')->placeholder('-'),
                TextEntry::make('type')->placeholder('-'),
                TextEntry::make('transaction_id')->placeholder('-')->copyable(),
                TextEntry::make('source')->placeholder('-'),
                IconEntry::make('synced')->boolean(),
                TextEntry::make('attempts')->placeholder('-'),
                TextEntry::make('event_date')->dateTime()->placeholder('-'),
                TextEntry::make('flow_metadata')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->columnSpanFull()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('block_id', 'desc')
            ->columns([
                TextColumn::make('id')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('block_id')->numeric(thousandsSeparator: '')->sortable()->searchable(),
                TextColumn::make('type')->searchable(),
                TextColumn::make('transaction_id')->searchable()->limit(16)->copyable(),
                TextColumn::make('source')->badge(),
                IconColumn::make('synced')->boolean(),
                TextColumn::make('attempts')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('event_date')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('synced'),
                SelectFilter::make('source')->options(['rest' => 'rest', 'stream' => 'stream', 'backfill' => 'backfill', 'reconcile' => 'reconcile'])
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'view' => Pages\ViewEvent::route('/{record}'),
        ];
    }
}
