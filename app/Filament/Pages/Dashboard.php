<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CustomAccountWidget;
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
        ];
    }
}
