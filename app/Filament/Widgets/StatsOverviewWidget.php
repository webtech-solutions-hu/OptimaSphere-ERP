<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::count())
                ->description('Active products in catalog')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Low Stock Items', Product::lowStock()->count())
                ->description('Products below reorder level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Total Customers', Customer::active()->count())
                ->description('Active customers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Suppliers', Supplier::active()->count())
                ->description('Active suppliers')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),

            Stat::make('Warehouses', Warehouse::active()->count())
                ->description('Active warehouse locations')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Out of Stock', Product::outOfStock()->count())
                ->description('Products with zero stock')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
