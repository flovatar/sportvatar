<?php

namespace App\Filament\Resources\Sportbits;

use App\Models\Sportbit;
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

class SportbitResource extends Resource
{
    protected static ?string $model = Sportbit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'NFTs';

    protected static ?string $navigationLabel = 'Sportbits';

    protected static ?string $pluralModelLabel = 'Sportbits';

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
                TextEntry::make('sportvatarTemplate.name')->placeholder('-'),
                TextEntry::make('sportvatarTemplate.layer')->placeholder('-'),
                TextEntry::make('flow_address')->placeholder('-')->copyable(),
                IconEntry::make('packed')->boolean(),
                IconEntry::make('destroyed')->boolean(),
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
                TextColumn::make('sportvatarTemplate.name'),
                TextColumn::make('sportvatarTemplate.layer')->numeric(thousandsSeparator: '')->sortable(),
                TextColumn::make('flow_address')->copyable()->fontFamily('mono')->searchable(),
                IconColumn::make('packed')->boolean(),
                IconColumn::make('destroyed')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('packed'),
                TernaryFilter::make('destroyed')
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('public')->label('Public page')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->url(fn ($record) => url('/components/'.$record->flow_id.'/'.$record->flow_address))->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSportbits::route('/'),
            'view' => Pages\ViewSportbit::route('/{record}'),
        ];
    }
}
