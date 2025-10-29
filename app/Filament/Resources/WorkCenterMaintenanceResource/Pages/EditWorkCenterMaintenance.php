<?php

namespace App\Filament\Resources\WorkCenterMaintenanceResource\Pages;

use App\Filament\Resources\WorkCenterMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkCenterMaintenance extends EditRecord
{
    protected static string $resource = WorkCenterMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
