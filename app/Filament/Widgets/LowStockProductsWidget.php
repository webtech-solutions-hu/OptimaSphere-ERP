<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProductsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('track_inventory', true)
                    ->whereColumn('current_stock', '<=', 'reorder_level')
                    ->where('current_stock', '>', 0)
                    ->orderBy('current_stock', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Product Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->suffix(fn ($record) => ' ' . ($record->unit->abbreviation ?? '')),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->sortable()
                    ->suffix(fn ($record) => ' ' . ($record->unit->abbreviation ?? '')),

                Tables\Columns\TextColumn::make('reorder_quantity')
                    ->label('Reorder Qty')
                    ->sortable()
                    ->default('N/A')
                    ->suffix(fn ($record) => $record->reorder_quantity ? ' ' . ($record->unit->abbreviation ?? '') : ''),
            ])
            ->heading('Low Stock Alert')
            ->description('Products that need to be reordered')
            ->defaultSort('current_stock', 'asc')
            ->emptyStateHeading('No low stock products')
            ->emptyStateDescription('All products are adequately stocked')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
