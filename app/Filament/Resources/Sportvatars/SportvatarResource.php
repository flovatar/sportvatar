<?php

namespace App\Filament\Resources\Sportvatars;

use App\Models\Sportvatar;
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

class SportvatarResource extends Resource
{
    protected static ?string $model = Sportvatar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'NFTs';

    protected static ?string $navigationLabel = 'Sportvatars';

    protected static ?string $pluralModelLabel = 'Sportvatars';

    // Blockchain-derived data: the indexer is authoritative, so the admin is read-only.
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
                TextEntry::make('flow_id')->placeholder('-'),
                TextEntry::make('mint')->placeholder('-'),
                TextEntry::make('name')->placeholder('-'),
                TextEntry::make('rarity')->placeholder('-'),
                TextEntry::make('ability')->placeholder('-'),
                TextEntry::make('flow_address')->placeholder('-')->copyable(),
                TextEntry::make('updated_at')->dateTime()->placeholder('-'),
                TextEntry::make('flow_metadata')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->columnSpanFull()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('flow_id', 'desc')
            ->columns([
                TextColumn::make('flow_id')->numeric(thousandsSeparator: '')->sortable()->searchable(),
                TextColumn::make('mint')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('rarity')->badge(),
                TextColumn::make('ability')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('flow_address')->copyable()->fontFamily('mono')->searchable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('rarity')->options(['common' => 'common', 'rare' => 'rare', 'epic' => 'epic', 'legendary' => 'legendary'])
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('public')->label('Public page')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->url(fn ($record) => url('/sportvatars/'.$record->flow_id.'/'.$record->flow_address))->openUrlInNewTab(),
                Action::make('resync')->label('Re-sync from chain')->icon(Heroicon::OutlinedArrowPath)->requiresConfirmation()->visible(fn ($record) => (bool) $record->flow_address)->action(function ($record) { \App\Jobs\SyncSportvatar::dispatch($record->flow_id, $record->flow_address); Notification::make()->title('Re-sync queued')->success()->send(); }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSportvatars::route('/'),
            'view' => Pages\ViewSportvatar::route('/{record}'),
        ];
    }
}
