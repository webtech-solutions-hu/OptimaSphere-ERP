<?php

namespace App\Filament\Resources\WarehouseTransferResource\Pages;

use App\Filament\Resources\WarehouseTransferResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWarehouseTransfer extends CreateRecord
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();
        $data['requested_date'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'warehouse_transfer_created',
            "Warehouse transfer {$this->record->reference} was created",
            $this->record,
            Auth::user()
        );
    }
}
