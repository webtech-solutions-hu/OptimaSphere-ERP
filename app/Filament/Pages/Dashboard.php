<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CustomAccountWidget;
use App\Filament\Widgets\LowStockProductsWidget;
use App\Filament\Widgets\RecentActivitiesWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WarehouseStockOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string $routePath = '/';

    public function getWidgets(): array
    {
        return [
            CustomAccountWidget::class,
            StatsOverviewWidget::class,
            LowStockProductsWidget::class,
            RecentActivitiesWidget::class,
            WarehouseStockOverviewWidget::class,
        ];
    }
}
