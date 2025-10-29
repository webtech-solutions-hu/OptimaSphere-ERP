<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionOrderOperationResource\Pages;
use App\Filament\Resources\ProductionOrderOperationResource\RelationManagers;
use App\Models\ProductionOrderOperation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductionOrderOperationResource extends Resource
{
    protected static ?string $model = ProductionOrderOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('production_order_id')
                    ->relationship('productionOrder', 'id')
                    ->required(),
                Forms\Components\Select::make('production_schedule_id')
                    ->relationship('productionSchedule', 'id')
                    ->default(null),
                Forms\Components\Select::make('work_center_id')
                    ->relationship('workCenter', 'name')
                    ->required(),
                Forms\Components\TextInput::make('operation_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sequence')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\DateTimePicker::make('paused_at'),
                Forms\Components\TextInput::make('total_pause_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('estimated_duration_minutes')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('actual_duration_minutes')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('quantity_to_process')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantity_completed')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('quantity_scrapped')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Select::make('operator_id')
                    ->relationship('operator', 'name')
                    ->default(null),
                Forms\Components\TextInput::make('started_by')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('completed_by')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('barcode')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('qr_code')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('operation_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('quality_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('custom_fields')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productionOrder.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('productionSchedule.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('workCenter.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operation_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sequence')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paused_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_pause_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_to_process')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_scrapped')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operator.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qr_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProductionOrderOperations::route('/'),
            'create' => Pages\CreateProductionOrderOperation::route('/create'),
            'edit' => Pages\EditProductionOrderOperation::route('/{record}/edit'),
        ];
    }
}
