<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'stock_adjustment_created',
            "Stock adjustment {$this->record->reference} was created",
            $this->record,
            Auth::user()
        );
    }
}
