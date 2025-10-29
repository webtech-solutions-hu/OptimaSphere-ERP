<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillOfMaterialResource\Pages;
use App\Models\BillOfMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class BillOfMaterialResource extends Resource
{
    protected static ?string $model = BillOfMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationGroup = 'Production & Manufacturing';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $navigationLabel = 'Bill of Materials';


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::pendingApproval()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('BOM Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The finished product this BOM produces'),

                        Forms\Components\TextInput::make('version')
                            ->default('1.0')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->isApproved()),

                        Forms\Components\Select::make('bom_type')
                            ->options([
                                'manufacturing' => 'Manufacturing',
                                'engineering' => 'Engineering',
                                'sales' => 'Sales',
                            ])
                            ->default('manufacturing')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'obsolete' => 'Obsolete',
                            ])
                            ->default('draft')
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quantity & Cost')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Output Quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(0)
                            ->helperText('Quantity this BOM produces'),

                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->relationship('unit', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('labor_cost')
                            ->label('Labor Cost')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),

                        Forms\Components\TextInput::make('overhead_cost')
                            ->label('Overhead Cost')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),

                        Forms\Components\Placeholder::make('total_cost')
                            ->label('Material Cost')
                            ->content(fn ($record) => $record ? '$' . number_format($record->total_cost, 2) : '$0.00')
                            ->helperText('Calculated from BOM items'),

                        Forms\Components\Placeholder::make('total_bom_cost')
                            ->label('Total BOM Cost')
                            ->content(fn ($record) => $record ? '$' . number_format($record->total_bom_cost, 2) : '$0.00')
                            ->helperText('Material + Labor + Overhead'),

                        Forms\Components\TextInput::make('estimated_time_minutes')
                            ->label('Estimated Production Time (minutes)')
                            ->numeric()
                            ->suffix('min'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('BOM Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Component/Material')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('unit_id', $product->unit_id);
                                                $set('unit_cost', $product->cost_price);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $quantity = floatval($get('quantity'));
                                        $unitCost = floatval($get('unit_cost'));
                                        $scrapPercentage = floatval($get('scrap_percentage'));

                                        $quantityWithScrap = $quantity * (1 + ($scrapPercentage / 100));
                                        $set('quantity_with_scrap', round($quantityWithScrap, 4));
                                        $set('total_cost', round($quantity * $unitCost, 2));
                                    }),

                                Forms\Components\Select::make('unit_id')
                                    ->label('Unit')
                                    ->relationship('unit', 'name')
                                    ->required(),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $quantity = floatval($get('quantity'));
                                        $unitCost = floatval($get('unit_cost'));
                                        $set('total_cost', round($quantity * $unitCost, 2));
                                    }),

                                Forms\Components\TextInput::make('scrap_percentage')
                                    ->label('Scrap %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $quantity = floatval($get('quantity'));
                                        $scrapPercentage = floatval($get('scrap_percentage'));
                                        $quantityWithScrap = $quantity * (1 + ($scrapPercentage / 100));
                                        $set('quantity_with_scrap', round($quantityWithScrap, 4));
                                    }),

                                Forms\Components\TextInput::make('sequence')
                                    ->label('Seq')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\Select::make('item_type')
                                    ->label('Type')
                                    ->options([
                                        'component' => 'Component',
                                        'raw_material' => 'Raw Material',
                                        'sub_assembly' => 'Sub-Assembly',
                                    ])
                                    ->default('component')
                                    ->required(),

                                Forms\Components\TextInput::make('reference_designator')
                                    ->label('Ref Designator')
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('is_optional')
                                    ->label('Optional')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                isset($state['product_id'])
                                    ? \App\Models\Product::find($state['product_id'])?->name
                                    : null
                            )
                            ->defaultItems(0)
                            ->addActionLabel('Add Component/Material'),
                    ])
                    ->visible(fn ($record) => $record && $record->canBeEdited())
                    ->collapsible(),

                Forms\Components\Section::make('Dates & Approval')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Effective Date'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Expiry Date'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->visible(fn ($record) => $record && $record->status === 'rejected')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),
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
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('version')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'obsolete',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_bom_cost')
                    ->label('Total Cost')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_latest_version')
                    ->label('Latest')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'obsolete' => 'Obsolete',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_latest_version')
                    ->label('Latest Version')
                    ->boolean()
                    ->trueLabel('Latest only')
                    ->falseLabel('Old versions only')
                    ->native(false),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),

                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->canBeEdited()),

                    Tables\Actions\Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->submitForApproval())
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('BOM submitted for approval')
                        ),

                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending_approval')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->approve(auth()->id()))
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('BOM approved')
                        ),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === 'pending_approval')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->reject(auth()->id(), $data['rejection_reason']);
                        })
                        ->successNotification(
                            fn () => \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('BOM rejected')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('BOM Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('name')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('version')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending_approval' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'obsolete' => 'secondary',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Product'),

                        Infolists\Components\TextEntry::make('bom_type')
                            ->label('BOM Type')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Cost Breakdown')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_cost')
                            ->label('Material Cost')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('labor_cost')
                            ->label('Labor Cost')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('overhead_cost')
                            ->label('Overhead Cost')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('total_bom_cost')
                            ->label('Total BOM Cost')
                            ->money('USD')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('estimated_time_minutes')
                            ->label('Est. Production Time')
                            ->suffix(' minutes'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('BOM Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Component/Material'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->suffix(fn ($record) => ' ' . $record->unit?->symbol),
                                Infolists\Components\TextEntry::make('unit_cost')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make('total_cost')
                                    ->money('USD'),
                            ])
                            ->columns(4),
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
            'index' => Pages\ListBillOfMaterials::route('/'),
            'create' => Pages\CreateBillOfMaterial::route('/create'),
            'edit' => Pages\EditBillOfMaterial::route('/{record}/edit'),
        ];
    }
}
