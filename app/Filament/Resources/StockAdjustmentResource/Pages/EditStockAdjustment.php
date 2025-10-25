<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditStockAdjustment extends EditRecord
{
    protected static string $resource = StockAdjustmentResource::class;

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
            'stock_adjustment_updated',
            "Stock adjustment {$this->record->reference} was updated",
            $this->record,
            Auth::user()
        );
    }
}
