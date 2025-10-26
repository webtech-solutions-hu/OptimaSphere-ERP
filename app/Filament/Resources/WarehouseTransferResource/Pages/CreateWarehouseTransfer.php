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
            Auth::user(),
            [
                'reference' => $this->record->reference,
                'from_warehouse_id' => $this->record->from_warehouse_id,
                'to_warehouse_id' => $this->record->to_warehouse_id,
                'product_id' => $this->record->product_id,
                'quantity' => $this->record->quantity,
            ]
        );
    }
}
