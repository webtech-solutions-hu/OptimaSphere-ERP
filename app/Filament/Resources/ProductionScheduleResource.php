<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionScheduleResource\Pages;
use App\Models\ProductionSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductionScheduleResource extends Resource
{
    protected static ?string $model = ProductionSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Production & Manufacturing';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $navigationLabel = 'Production Schedules';


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::withConflicts()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $conflictCount = static::getModel()::withConflicts()->count();
        return $conflictCount > 0 ? 'danger' : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('production_order_id')
                            ->label('Production Order')
                            ->relationship('productionOrder', 'reference', function ($query) {
                                $query->whereNotIn('status', ['completed', 'cancelled']);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('work_center_id')
                            ->label('Work Center')
                            ->relationship('workCenter', 'name', function ($query) {
                                $query->available();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('operation_name')
                            ->label('Operation Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sequence')
                            ->label('Sequence')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Order of operations in the production process'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'ready' => 'Ready',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'on_hold' => 'On Hold',
                            ])
                            ->default('scheduled')
                            ->disabled(),

                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('normal')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule Times')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_start')
                            ->label('Scheduled Start')
                            ->required()
                            ->live()
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('scheduled_end')
                            ->label('Scheduled End')
                            ->required()
                            ->after('scheduled_start')
                            ->seconds(false),

                        Forms\Components\TextInput::make('setup_time_minutes')
                            ->label('Setup Time (minutes)')
                            ->numeric()
                            ->default(0)
                            ->suffix('min')
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $setup = floatval($get('setup_time_minutes'));
                                $run = floatval($get('run_time_minutes'));
                                $teardown = floatval($get('teardown_time_minutes'));
                                $set('estimated_duration_minutes', $setup + $run + $teardown);
                            }),

                        Forms\Components\TextInput::make('run_time_minutes')
                            ->label('Run Time (minutes)')
                            ->numeric()
                            ->default(0)
                            ->suffix('min')
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $setup = floatval($get('setup_time_minutes'));
                                $run = floatval($get('run_time_minutes'));
                                $teardown = floatval($get('teardown_time_minutes'));
                                $set('estimated_duration_minutes', $setup + $run + $teardown);
                            }),

                        Forms\Components\TextInput::make('teardown_time_minutes')
                            ->label('Teardown Time (minutes)')
                            ->numeric()
                            ->default(0)
                            ->suffix('min')
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $setup = floatval($get('setup_time_minutes'));
                                $run = floatval($get('run_time_minutes'));
                                $teardown = floatval($get('teardown_time_minutes'));
                                $set('estimated_duration_minutes', $setup + $run + $teardown);
                            }),

                        Forms\Components\Placeholder::make('estimated_duration_minutes')
                            ->label('Total Estimated Duration')
                            ->content(fn ($record, Forms\Get $get) => ($record?->estimated_duration_minutes ?? $get('estimated_duration_minutes') ?? 0) . ' minutes'),

                        Forms\Components\Placeholder::make('actual_duration_minutes')
                            ->label('Actual Duration')
                            ->content(fn ($record) => $record && $record->actual_duration_minutes ? $record->actual_duration_minutes . ' minutes' : 'Not completed')
                            ->visible(fn ($record) => $record),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Quantity & Assignment')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_scheduled')
                            ->label('Quantity Scheduled')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(0),

                        Forms\Components\Placeholder::make('quantity_completed')
                            ->label('Quantity Completed')
                            ->content(fn ($record) => $record ? number_format($record->quantity_completed, 2) : '0')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('quantity_scrapped')
                            ->label('Quantity Scrapped')
                            ->content(fn ($record) => $record ? number_format($record->quantity_scrapped, 2) : '0')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Select::make('assigned_operator_id')
                            ->label('Assigned Operator')
                            ->relationship('assignedOperator', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Conflict Detection')
                    ->schema([
                        Forms\Components\Placeholder::make('has_conflict')
                            ->label('Conflict Status')
                            ->content(fn ($record) => $record && $record->has_conflict
                                ? '⚠️ This schedule has conflicts'
                                : '✓ No conflicts detected'
                            )
                            ->visible(fn ($record) => $record),

                        Forms\Components\Textarea::make('conflict_details')
                            ->label('Conflict Details')
                            ->rows(3)
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->has_conflict)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record)
                    ->collapsed(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('operation_notes')
                            ->label('Operation Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('completion_notes')
                            ->label('Completion Notes')
                            ->rows(3)
                            ->visible(fn ($record) => $record && $record->status === 'completed')
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

                Tables\Columns\TextColumn::make('productionOrder.reference')
                    ->label('Production Order')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('workCenter.name')
                    ->label('Work Center')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('operation_name')
                    ->label('Operation')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'scheduled',
                        'info' => 'ready',
                        'warning' => 'in_progress',
                        'success' => 'completed',
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

                Tables\Columns\TextColumn::make('scheduled_start')
                    ->label('Start')
                    ->dateTime('M j, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_end')
                    ->label('End')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\IconColumn::make('has_conflict')
                    ->label('Conflict')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('assignedOperator.name')
                    ->label('Operator')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'ready' => 'Ready',
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

                Tables\Filters\SelectFilter::make('work_center_id')
                    ->label('Work Center')
                    ->relationship('workCenter', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assigned_operator_id')
                    ->label('Operator')
                    ->relationship('assignedOperator', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_conflict')
                    ->query(fn ($query) => $query->withConflicts())
                    ->label('With Conflicts Only'),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->overdue())
                    ->label('Overdue Only'),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('From'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($q) => $q->where('scheduled_start', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->where('scheduled_start', '<=', $data['end_date']));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),

                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === 'scheduled'),

                    Tables\Actions\Action::make('start')
                        ->label('Start')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['scheduled', 'ready']))
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->start(auth()->id()))
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Schedule started')
                        ),

                    Tables\Actions\Action::make('complete')
                        ->label('Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->form([
                            Forms\Components\TextInput::make('quantity_completed')
                                ->label('Quantity Completed')
                                ->numeric()
                                ->required()
                                ->minValue(0),
                            Forms\Components\TextInput::make('quantity_scrapped')
                                ->label('Quantity Scrapped')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            Forms\Components\Textarea::make('completion_notes')
                                ->label('Completion Notes')
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->complete(
                                auth()->id(),
                                $data['quantity_completed'],
                                $data['quantity_scrapped']
                            );
                            if (isset($data['completion_notes'])) {
                                $record->completion_notes = $data['completion_notes'];
                                $record->save();
                            }
                        })
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Schedule completed')
                        ),

                    Tables\Actions\Action::make('hold')
                        ->label('Put on Hold')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->putOnHold())
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Schedule put on hold')
                        ),

                    Tables\Actions\Action::make('resume')
                        ->label('Resume')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'on_hold')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->resume())
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Schedule resumed')
                        ),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->cancel())
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Schedule cancelled')
                        ),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->status === 'scheduled'),
                ])
                ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_start', 'asc');
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
            'index' => Pages\ListProductionSchedules::route('/'),
            'create' => Pages\CreateProductionSchedule::route('/create'),
            'edit' => Pages\EditProductionSchedule::route('/{record}/edit'),
        ];
    }
}
