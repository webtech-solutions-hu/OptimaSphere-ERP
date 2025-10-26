<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\ActivityLog;
use App\Models\ProductWarehouseStock;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();

        // Get current stock balance
        $stock = ProductWarehouseStock::where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->first();

        $balanceBefore = $stock ? $stock->quantity : 0;
        $data['balance_before'] = $balanceBefore;

        // Calculate balance after based on adjustment type
        if ($data['type'] === 'increase') {
            $data['balance_after'] = $balanceBefore + $data['quantity'];
        } else {
            $data['balance_after'] = $balanceBefore - $data['quantity'];
        }

        // Calculate total cost if unit cost is provided
        if (isset($data['unit_cost']) && isset($data['quantity'])) {
            $data['total_cost'] = $data['unit_cost'] * $data['quantity'];
        }

        // Set default status to pending_approval
        $data['status'] = 'pending_approval';

        return $data;
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'stock_adjustment_created',
            "Stock adjustment {$this->record->reference} was created",
            Auth::user(),
            [
                'reference' => $this->record->reference,
                'warehouse_id' => $this->record->warehouse_id,
                'product_id' => $this->record->product_id,
                'type' => $this->record->type,
                'quantity' => $this->record->quantity,
            ]
        );
    }
}
