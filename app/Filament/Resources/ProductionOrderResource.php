<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionOrderResource\Pages;
use App\Models\ProductionOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Production & Manufacturing';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'reference';


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Production Order Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('bill_of_material_id')
                            ->label('Bill of Material')
                            ->relationship('billOfMaterial', 'reference', function ($query) {
                                $query->effective();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $bom = \App\Models\BillOfMaterial::find($state);
                                    if ($bom) {
                                        $set('product_id', $bom->product_id);
                                        $set('unit_id', $bom->unit_id);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('product_id')
                            ->label('Product to Produce')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Forms\Get $get) => $get('bill_of_material_id')),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Production Warehouse')
                            ->relationship('warehouse', 'name', function ($query) {
                                $query->where('is_active', true);
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('quantity_to_produce')
                            ->label('Quantity to Produce')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1),

                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->relationship('unit', 'name')
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('normal')
                            ->required(),

                        Forms\Components\Select::make('material_allocation_mode')
                            ->label('Material Allocation')
                            ->options([
                                'auto' => 'Automatic',
                                'manual' => 'Manual',
                            ])
                            ->default('auto')
                            ->required()
                            ->helperText('Auto: Materials reserved on order release. Manual: Materials picked manually.'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'planned' => 'Planned',
                                'released' => 'Released',
                                'materials_reserved' => 'Materials Reserved',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'on_hold' => 'On Hold',
                            ])
                            ->default('draft')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('planned_start_date')
                            ->label('Planned Start Date')
                            ->required(),

                        Forms\Components\DatePicker::make('planned_end_date')
                            ->label('Planned End Date')
                            ->required()
                            ->after('planned_start_date'),

                        Forms\Components\Placeholder::make('actual_start_date')
                            ->label('Actual Start Date')
                            ->content(fn ($record) => $record?->actual_start_date?->format('Y-m-d H:i') ?? 'Not started')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('actual_end_date')
                            ->label('Actual End Date')
                            ->content(fn ($record) => $record?->actual_end_date?->format('Y-m-d H:i') ?? 'Not completed')
                            ->visible(fn ($record) => $record),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Sales Order Link')
                    ->schema([
                        Forms\Components\Select::make('sales_order_id')
                            ->label('Sales Order')
                            ->relationship('salesOrder', 'order_number')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('sales_order_reference')
                            ->label('Sales Order Reference')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('customer_reference')
                            ->label('Customer Reference')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Assignment & Cost')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Placeholder::make('estimated_cost')
                            ->label('Estimated Cost')
                            ->content(fn ($record) => $record ? '$' . number_format($record->estimated_cost, 2) : '$0.00'),

                        Forms\Components\Placeholder::make('actual_cost')
                            ->label('Actual Cost')
                            ->content(fn ($record) => $record ? '$' . number_format($record->actual_cost, 2) : '$0.00')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('estimated_time_minutes')
                            ->label('Estimated Time')
                            ->content(fn ($record) => $record ? $record->estimated_time_minutes . ' min' : '0 min'),

                        Forms\Components\Placeholder::make('actual_time_minutes')
                            ->label('Actual Time')
                            ->content(fn ($record) => $record ? ($record->actual_time_minutes ?? '0') . ' min' : '0 min')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('quantity_produced')
                            ->label('Quantity Produced')
                            ->content(fn ($record) => $record ? number_format($record->quantity_produced, 2) : '0')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('quantity_scrapped')
                            ->label('Quantity Scrapped')
                            ->content(fn ($record) => $record ? number_format($record->quantity_scrapped, 2) : '0')
                            ->visible(fn ($record) => $record),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('production_notes')
                            ->label('Production Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('quality_notes')
                            ->label('Quality Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->rows(3)
                            ->visible(fn ($record) => $record && $record->status === 'cancelled')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quantity_to_produce')
                    ->label('Qty')
                    ->numeric()
                    ->suffix(fn ($record) => ' ' . $record->unit?->symbol)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'planned',
                        'warning' => 'released',
                        'primary' => 'materials_reserved',
                        'success' => fn ($state) => in_array($state, ['in_progress', 'completed']),
                        'danger' => 'cancelled',
                        'secondary' => 'on_hold',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('planned_start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->color(fn ($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->visible(fn ($record) => $record && $record->status === 'in_progress'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'planned' => 'Planned',
                        'released' => 'Released',
                        'materials_reserved' => 'Materials Reserved',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'on_hold' => 'On Hold',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->overdue())
                    ->label('Overdue Only'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),

                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === 'draft'),

                    Tables\Actions\Action::make('release')
                        ->label('Release')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->status = 'released';
                            $record->save();

                            // Auto-reserve materials if mode is auto
                            if ($record->material_allocation_mode === 'auto') {
                                $record->reserveMaterials();
                            }
                        })
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Production order released')
                        ),

                    Tables\Actions\Action::make('start')
                        ->label('Start Production')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn ($record) => in_array($record->status, ['released', 'materials_reserved']))
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->start(auth()->id()))
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Production started')
                        ),

                    Tables\Actions\Action::make('complete')
                        ->label('Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->complete(auth()->id()))
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Production completed')
                        ),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->form([
                            Forms\Components\Textarea::make('cancellation_reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->cancel(auth()->id(), $data['cancellation_reason']);
                        })
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Production order cancelled')
                        ),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->status === 'draft'),
                ])
                ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProductionOrders::route('/'),
            'create' => Pages\CreateProductionOrder::route('/create'),
            'edit' => Pages\EditProductionOrder::route('/{record}/edit'),
        ];
    }
}
