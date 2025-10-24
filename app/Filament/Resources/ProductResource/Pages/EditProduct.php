<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $changes = [];
        $dirtyFields = $this->record->getChanges();

        if (isset($dirtyFields['base_price'])) {
            $changes['price'] = "Price changed to \${$dirtyFields['base_price']}";
        }

        if (isset($dirtyFields['current_stock'])) {
            $changes['stock'] = "Stock updated to {$dirtyFields['current_stock']}";
        }

        if (isset($dirtyFields['is_active'])) {
            $changes['status'] = $dirtyFields['is_active'] ? 'Activated' : 'Deactivated';
        }

        if (isset($dirtyFields['is_featured'])) {
            $changes['featured'] = $dirtyFields['is_featured'] ? 'Marked as featured' : 'Removed from featured';
        }

        if (isset($dirtyFields['category_id'])) {
            $changes['category'] = 'Category changed';
        }

        $changeDescription = !empty($changes)
            ? ': ' . implode(', ', $changes)
            : '';

        ActivityLog::log(
            'product_updated',
            "Product '{$this->record->name}' was updated by " . Auth::user()->name . $changeDescription,
            Auth::user(),
            [
                'product_code' => $this->record->code,
                'product_id' => $this->record->id,
                'sku' => $this->record->sku,
                'changes' => $dirtyFields,
            ]
        );
    }
}
