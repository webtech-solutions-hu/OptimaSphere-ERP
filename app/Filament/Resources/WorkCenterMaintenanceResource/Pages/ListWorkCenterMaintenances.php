<?php

namespace App\Filament\Resources\WorkCenterMaintenanceResource\Pages;

use App\Filament\Resources\WorkCenterMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkCenterMaintenances extends ListRecords
{
    protected static string $resource = WorkCenterMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
