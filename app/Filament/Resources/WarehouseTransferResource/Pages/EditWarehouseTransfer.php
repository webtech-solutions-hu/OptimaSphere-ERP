<?php

namespace App\Filament\Resources\WarehouseTransferResource\Pages;

use App\Filament\Resources\WarehouseTransferResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWarehouseTransfer extends EditRecord
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        ActivityLog::log(
            'warehouse_transfer_updated',
            "Warehouse transfer {$this->record->reference} was updated",
            $this->record,
            Auth::user()
        );
    }
}
