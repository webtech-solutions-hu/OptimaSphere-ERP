<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseTransferResource\Pages;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\WarehouseTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WarehouseTransferResource extends Resource
{
    protected static ?string $model = WarehouseTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Procurement & Inventory';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::pending()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transfer Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'in_transit' => 'In Transit',
                                'received' => 'Received',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Warehouses')
                    ->schema([
                        Forms\Components\Select::make('from_warehouse_id')
                            ->label('From Warehouse')
                            ->relationship('fromWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('product_id', null))
                            ->distinct(fn (Forms\Get $get) => $get('to_warehouse_id')),

                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('To Warehouse')
                            ->relationship('toWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->distinct(fn (Forms\Get $get) => $get('from_warehouse_id')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product & Quantity')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship(
                                name: 'product',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, Forms\Get $get) => $query
                                    ->when(
                                        $get('from_warehouse_id'),
                                        fn ($q, $warehouseId) => $q->whereHas(
                                            'warehouseStock',
                                            fn ($stockQuery) => $stockQuery
                                                ->where('warehouse_id', $warehouseId)
                                                ->where('quantity', '>', 0)
                                        )
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if (!$state || !$get('from_warehouse_id')) {
                                    return;
                                }

                                // Get product details
                                $product = Product::find($state);
                                if (!$product) {
                                    return;
                                }

                                // Get stock in the selected warehouse
                                $stock = ProductWarehouseStock::where('product_id', $state)
                                    ->where('warehouse_id', $get('from_warehouse_id'))
                                    ->first();

                                // Set unit cost from product
                                if ($product->cost_price) {
                                    $set('unit_cost', $product->cost_price);
                                }

                                // Set max available quantity if stock exists
                                if ($stock && $stock->available_quantity > 0) {
                                    $set('quantity', $stock->available_quantity);
                                }
                            })
                            ->helperText(fn (Forms\Get $get) =>
                                $get('from_warehouse_id')
                                    ? 'Only products available in the selected warehouse are shown'
                                    : 'Please select a warehouse first'
                            ),

                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(function (Forms\Get $get) {
                                if (!$get('product_id') || !$get('from_warehouse_id')) {
                                    return null;
                                }

                                $stock = ProductWarehouseStock::where('product_id', $get('product_id'))
                                    ->where('warehouse_id', $get('from_warehouse_id'))
                                    ->first();

                                return $stock ? $stock->available_quantity : null;
                            })
                            ->suffix(function (Forms\Get $get) {
                                if (!$get('product_id')) {
                                    return '';
                                }

                                $product = Product::find($get('product_id'));
                                return $product?->unit?->symbol ?? '';
                            })
                            ->helperText(function (Forms\Get $get) {
                                if (!$get('product_id') || !$get('from_warehouse_id')) {
                                    return null;
                                }

                                $stock = ProductWarehouseStock::where('product_id', $get('product_id'))
                                    ->where('warehouse_id', $get('from_warehouse_id'))
                                    ->first();

                                if (!$stock) {
                                    return 'No stock available';
                                }

                                return "Available: {$stock->available_quantity} (Reserved: {$stock->reserved_quantity})";
                            }),

                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->helperText('Cost price per unit'),

                        Forms\Components\DatePicker::make('expected_delivery_date')
                            ->label('Expected Delivery')
                            ->minDate(today()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Shipping Details')
                    ->schema([
                        Forms\Components\TextInput::make('carrier')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tracking_number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('shipping_cost')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Notes')
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
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fromWarehouse.name')
                    ->label('From')
                    ->searchable(),

                Tables\Columns\TextColumn::make('toWarehouse.name')
                    ->label('To')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(2),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'info',
                        'in_transit' => 'primary',
                        'received' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Expected')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('requested_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_warehouse')
                    ->relationship('fromWarehouse', 'name'),

                Tables\Filters\SelectFilter::make('to_warehouse')
                    ->relationship('toWarehouse', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'in_transit' => 'In Transit',
                        'received' => 'Received',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'rejected'])),

                    Tables\Actions\Action::make('submit')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'pending_approval']))
                        ->visible(fn ($record) => $record->status === 'draft'),

                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->approve(Auth::user()))
                        ->visible(fn ($record) => $record->status === 'pending_approval'),

                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(fn ($record, array $data) => $record->reject(Auth::user(), $data['rejection_reason']))
                        ->visible(fn ($record) => $record->status === 'pending_approval'),

                    Tables\Actions\Action::make('ship')
                        ->label('Mark as Shipped')
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->form([
                            Forms\Components\TextInput::make('carrier'),
                            Forms\Components\TextInput::make('tracking_number'),
                        ])
                        ->action(fn ($record, array $data) => $record->markAsShipped(Auth::user(), $data['carrier'] ?? null, $data['tracking_number'] ?? null))
                        ->visible(fn ($record) => $record->status === 'approved'),

                    Tables\Actions\Action::make('receive')
                        ->label('Mark as Received')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->markAsReceived(Auth::user()))
                        ->visible(fn ($record) => $record->status === 'in_transit'),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'rejected'])),
                ]),
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
                Infolists\Components\Section::make('Transfer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending_approval' => 'warning',
                                'approved' => 'info',
                                'in_transit' => 'primary',
                                'received' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('requested_date')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Transfer Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('fromWarehouse.name')
                            ->label('From Warehouse'),
                        Infolists\Components\TextEntry::make('toWarehouse.name')
                            ->label('To Warehouse'),
                        Infolists\Components\TextEntry::make('product.name'),
                        Infolists\Components\TextEntry::make('quantity')
                            ->numeric(2),
                        Infolists\Components\TextEntry::make('unit_cost')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('total_cost')
                            ->money('USD'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Shipping Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('carrier')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('tracking_number')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('shipping_cost')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('expected_delivery_date')
                            ->date()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('requestedBy.name')
                            ->label('Requested By'),
                        Infolists\Components\TextEntry::make('approvedBy.name')
                            ->label('Approved By')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('shippedBy.name')
                            ->label('Shipped By')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('receivedBy.name')
                            ->label('Received By')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('approved_date')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('shipped_date')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('received_date')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->status === 'rejected'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseTransfers::route('/'),
            'create' => Pages\CreateWarehouseTransfer::route('/create'),
            'view' => Pages\ViewWarehouseTransfer::route('/{record}'),
            'edit' => Pages\EditWarehouseTransfer::route('/{record}/edit'),
        ];
    }
}
