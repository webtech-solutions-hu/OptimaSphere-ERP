<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkCenterResource\Pages;
use App\Models\WorkCenter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkCenterResource extends Resource
{
    protected static ?string $model = WorkCenter::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Production & Manufacturing';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Work Center Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->options([
                                'machine' => 'Machine',
                                'manual' => 'Manual',
                                'assembly' => 'Assembly',
                                'quality_control' => 'Quality Control',
                                'packaging' => 'Packaging',
                            ])
                            ->required(),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Warehouse Location')
                            ->relationship('warehouse', 'name', function ($query) {
                                $query->where('is_active', true);
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('location_details')
                            ->label('Location Details')
                            ->maxLength(255)
                            ->helperText('e.g., Floor 2, Bay A, Line 3')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Capacity & Performance')
                    ->schema([
                        Forms\Components\TextInput::make('capacity_per_day')
                            ->label('Capacity per Day')
                            ->numeric()
                            ->default(8)
                            ->required()
                            ->minValue(0),

                        Forms\Components\Select::make('capacity_unit')
                            ->label('Capacity Unit')
                            ->options([
                                'hours' => 'Hours',
                                'units' => 'Units',
                                'shifts' => 'Shifts',
                            ])
                            ->default('hours')
                            ->required(),

                        Forms\Components\TextInput::make('efficiency_percentage')
                            ->label('Efficiency %')
                            ->numeric()
                            ->default(100)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Expected efficiency compared to theoretical maximum'),

                        Forms\Components\Placeholder::make('utilization_percentage')
                            ->label('Current Utilization %')
                            ->content(fn ($record) => $record ? number_format($record->utilization_percentage, 1) . '%' : '0%')
                            ->visible(fn ($record) => $record),

                        Forms\Components\TextInput::make('cost_per_hour')
                            ->label('Cost per Hour')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Time Settings')
                    ->schema([
                        Forms\Components\TextInput::make('setup_time_minutes')
                            ->label('Setup Time (minutes)')
                            ->numeric()
                            ->default(0)
                            ->suffix('min')
                            ->minValue(0),

                        Forms\Components\TextInput::make('teardown_time_minutes')
                            ->label('Teardown Time (minutes)')
                            ->numeric()
                            ->default(0)
                            ->suffix('min')
                            ->minValue(0),

                        Forms\Components\TextInput::make('minimum_batch_size')
                            ->label('Minimum Batch Size')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),

                        Forms\Components\TextInput::make('maximum_batch_size')
                            ->label('Maximum Batch Size')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for no limit'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Operator Requirements')
                    ->schema([
                        Forms\Components\Toggle::make('requires_operator')
                            ->label('Requires Operator')
                            ->default(true)
                            ->inline(false)
                            ->live(),

                        Forms\Components\TextInput::make('required_operators')
                            ->label('Number of Operators Required')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->visible(fn (Forms\Get $get) => $get('requires_operator')),

                        Forms\Components\Select::make('supervisor_id')
                            ->label('Supervisor')
                            ->relationship('supervisor', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Capabilities & Certifications')
                    ->schema([
                        Forms\Components\TagsInput::make('capabilities')
                            ->placeholder('Add capability')
                            ->helperText('e.g., welding, cutting, drilling, assembly')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('certifications')
                            ->placeholder('Add certification')
                            ->helperText('e.g., ISO 9001, CE, UL')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Maintenance')
                    ->schema([
                        Forms\Components\DatePicker::make('maintenance_due_date')
                            ->label('Next Maintenance Due Date'),

                        Forms\Components\Textarea::make('maintenance_notes')
                            ->label('Maintenance Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Status & Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_available')
                            ->label('Available')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Temporarily unavailable due to maintenance, etc.'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'machine',
                        'success' => 'manual',
                        'warning' => 'assembly',
                        'info' => 'quality_control',
                        'secondary' => 'packaging',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Location')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('capacity_per_day')
                    ->label('Capacity')
                    ->formatStateUsing(fn ($record) => $record->capacity_per_day . ' ' . $record->capacity_unit)
                    ->sortable(),

                Tables\Columns\TextColumn::make('utilization_percentage')
                    ->label('Utilization')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('efficiency_percentage')
                    ->label('Efficiency')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('maintenance_due_date')
                    ->label('Maintenance Due')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isMaintenanceOverdue() ? 'danger' : ($record->isMaintenanceDue() ? 'warning' : null))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'machine' => 'Machine',
                        'manual' => 'Manual',
                        'assembly' => 'Assembly',
                        'quality_control' => 'Quality Control',
                        'packaging' => 'Packaging',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->trueLabel('Available only')
                    ->falseLabel('Unavailable only')
                    ->native(false),

                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('maintenance_due')
                    ->query(fn ($query) => $query->maintenanceDue())
                    ->label('Maintenance Due'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'asc');
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
            'index' => Pages\ListWorkCenters::route('/'),
            'create' => Pages\CreateWorkCenter::route('/create'),
            'edit' => Pages\EditWorkCenter::route('/{record}/edit'),
        ];
    }
}
