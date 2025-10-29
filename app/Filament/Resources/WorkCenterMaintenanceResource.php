<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkCenterMaintenanceResource\Pages;
use App\Filament\Resources\WorkCenterMaintenanceResource\RelationManagers;
use App\Models\WorkCenterMaintenance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkCenterMaintenanceResource extends Resource
{
    protected static ?string $model = WorkCenterMaintenance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('work_center_id')
                    ->relationship('workCenter', 'name')
                    ->required(),
                Forms\Components\TextInput::make('maintenance_type')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DatePicker::make('scheduled_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\TextInput::make('estimated_duration_minutes')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('actual_duration_minutes')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('estimated_cost')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('actual_cost')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('work_performed')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('parts_used')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('assigned_to')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('performed_by')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('approved_by')
                    ->numeric()
                    ->default(null),
                Forms\Components\DateTimePicker::make('approved_at'),
                Forms\Components\TextInput::make('priority')
                    ->required(),
                Forms\Components\Toggle::make('affects_production')
                    ->required(),
                Forms\Components\DatePicker::make('next_maintenance_date'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('attachments')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('workCenter.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenance_type'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_to')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('performed_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority'),
                Tables\Columns\IconColumn::make('affects_production')
                    ->boolean(),
                Tables\Columns\TextColumn::make('next_maintenance_date')
                    ->date()
                    ->sortable(),
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
            'index' => Pages\ListWorkCenterMaintenances::route('/'),
            'create' => Pages\CreateWorkCenterMaintenance::route('/create'),
            'edit' => Pages\EditWorkCenterMaintenance::route('/{record}/edit'),
        ];
    }
}
