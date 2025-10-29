<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialRequisitionResource\Pages;
use App\Models\MaterialRequisition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaterialRequisitionResource extends Resource
{
    protected static ?string $model = MaterialRequisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Production & Manufacturing';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'reference';


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'submitted')->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Requisition Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('production_order_id')
                            ->label('Production Order')
                            ->relationship('productionOrder', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'automatic' => 'Automatic',
                                'manual' => 'Manual',
                            ])
                            ->default('manual')
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

                        Forms\Components\DatePicker::make('required_date')
                            ->label('Required Date'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                                'approved' => 'Approved',
                                'picking' => 'Picking',
                                'issued' => 'Issued',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
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

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'submitted',
                        'info' => 'approved',
                        'primary' => 'picking',
                        'success' => fn($state) => in_array($state, ['issued', 'completed']),
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),

                Tables\Columns\TextColumn::make('required_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'picking' => 'Picking',
                        'issued' => 'Issued',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMaterialRequisitions::route('/'),
            'create' => Pages\CreateMaterialRequisition::route('/create'),
            'edit' => Pages\EditMaterialRequisition::route('/{record}/edit'),
        ];
    }
}
