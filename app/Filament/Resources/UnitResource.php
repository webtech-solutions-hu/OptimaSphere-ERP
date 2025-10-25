<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Product Management';

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
                Forms\Components\Section::make('Unit Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('e.g., KG, L, PCS, M')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., Kilogram, Liter, Pieces, Meter')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('symbol')
                            ->maxLength(255)
                            ->helperText('e.g., kg, L, pcs, m'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'weight' => 'Weight',
                                'volume' => 'Volume',
                                'length' => 'Length',
                                'area' => 'Area',
                                'quantity' => 'Quantity',
                                'time' => 'Time',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('quantity'),

                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Unit Conversion')
                    ->schema([
                        Forms\Components\Select::make('base_unit_id')
                            ->label('Base Unit')
                            ->relationship('baseUnit', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty if this is a base unit')
                            ->live(),

                        Forms\Components\TextInput::make('conversion_factor')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(0)
                            ->step(0.000001)
                            ->helperText('Multiplier to convert to base unit (e.g., 1000 for kg to g)')
                            ->visible(fn (Forms\Get $get) => filled($get('base_unit_id'))),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('symbol')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'weight',
                        'success' => 'volume',
                        'warning' => 'length',
                        'info' => 'quantity',
                        'danger' => 'other',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('baseUnit.name')
                    ->label('Base Unit')
                    ->badge()
                    ->color('secondary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('conversion_factor')
                    ->label('Factor')
                    ->numeric(decimalPlaces: 6)
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'weight' => 'Weight',
                        'volume' => 'Volume',
                        'length' => 'Length',
                        'area' => 'Area',
                        'quantity' => 'Quantity',
                        'time' => 'Time',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->slideOver(),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
