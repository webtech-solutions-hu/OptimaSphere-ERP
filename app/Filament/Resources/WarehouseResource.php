<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Procurement & Inventory';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Warehouse Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Warehouse Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->options([
                                'main' => 'Main Warehouse',
                                'regional' => 'Regional Warehouse',
                                'distribution' => 'Distribution Center',
                                'storage' => 'Storage Facility',
                                'transit' => 'Transit Hub',
                            ])
                            ->required()
                            ->default('main')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Warehouse')
                            ->helperText('Set as default warehouse for operations')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(-90)
                            ->maxValue(90),

                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(-180)
                            ->maxValue(180),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('manager_name')
                            ->label('Manager Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Capacity & Operations')
                    ->schema([
                        Forms\Components\TextInput::make('storage_capacity')
                            ->label('Storage Capacity (m³)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('m³')
                            ->helperText('Total storage capacity in cubic meters'),

                        Forms\Components\TextInput::make('current_utilization')
                            ->label('Current Utilization (m³)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('m³')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('accepts_inbound')
                            ->label('Accepts Inbound')
                            ->helperText('Can receive stock')
                            ->default(true),

                        Forms\Components\Toggle::make('accepts_outbound')
                            ->label('Accepts Outbound')
                            ->helperText('Can ship stock')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'main' => 'success',
                        'regional' => 'info',
                        'distribution' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('manager_name')
                    ->label('Manager')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('storage_capacity')
                    ->label('Capacity')
                    ->suffix(' m³')
                    ->numeric(2)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('utilization_percentage')
                    ->label('Utilization')
                    ->suffix('%')
                    ->numeric(2)
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        default => 'success',
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'main' => 'Main Warehouse',
                        'regional' => 'Regional Warehouse',
                        'distribution' => 'Distribution Center',
                        'storage' => 'Storage Facility',
                        'transit' => 'Transit Hub',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Warehouse'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Warehouse Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label('Warehouse Code'),
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'main' => 'success',
                                'regional' => 'info',
                                'distribution' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\IconEntry::make('is_primary')
                            ->label('Primary Warehouse')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Location')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_address')
                            ->label('Address')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('latitude'),
                        Infolists\Components\TextEntry::make('longitude'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('manager_name')
                            ->label('Manager'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('email'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Capacity')
                    ->schema([
                        Infolists\Components\TextEntry::make('storage_capacity')
                            ->label('Total Capacity')
                            ->suffix(' m³'),
                        Infolists\Components\TextEntry::make('current_utilization')
                            ->label('Current Usage')
                            ->suffix(' m³'),
                        Infolists\Components\TextEntry::make('utilization_percentage')
                            ->label('Utilization')
                            ->suffix('%')
                            ->color(fn ($state): string => match (true) {
                                $state === null => 'gray',
                                $state >= 90 => 'danger',
                                $state >= 70 => 'warning',
                                default => 'success',
                            }),
                        Infolists\Components\IconEntry::make('accepts_inbound')
                            ->label('Accepts Inbound')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('accepts_outbound')
                            ->label('Accepts Outbound')
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'view' => Pages\ViewWarehouse::route('/{record}'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
