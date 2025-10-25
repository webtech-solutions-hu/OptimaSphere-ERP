<?php

namespace App\Filament\Widgets;

use App\Models\ProductWarehouseStock;
use App\Models\Warehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class WarehouseStockOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Warehouse::query()
                    ->with(['productStock' => function ($query) {
                        $query->with('product');
                    }])
                    ->withCount(['productStock as total_products'])
                    ->where('is_active', true)
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Warehouse Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Warehouse Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'main' => 'primary',
                        'regional' => 'info',
                        'retail' => 'success',
                        'transit' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_products')
                    ->label('Products')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Total Stock Value')
                    ->getStateUsing(function ($record) {
                        return $record->productStock->sum(function ($stock) {
                            return $stock->quantity * ($stock->product->cost_price ?? 0);
                        });
                    })
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Units')
                    ->getStateUsing(function ($record) {
                        return number_format($record->productStock->sum('quantity'), 0);
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->heading('Warehouse Stock Overview')
            ->description('Stock distribution across warehouses')
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('No warehouses configured')
            ->emptyStateDescription('Create warehouses to track inventory')
            ->emptyStateIcon('heroicon-o-building-storefront');
    }
}
