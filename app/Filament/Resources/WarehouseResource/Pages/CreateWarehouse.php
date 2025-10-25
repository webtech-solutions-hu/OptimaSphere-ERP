<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'warehouse_created',
            "Warehouse {$this->record->name} ({$this->record->code}) was created",
            $this->record,
            Auth::user()
        );
    }
}
