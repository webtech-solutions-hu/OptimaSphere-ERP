<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

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

        // Track specific important changes
        if (isset($dirtyFields['type'])) {
            $changes['type'] = "Customer type changed to {$dirtyFields['type']}";
        }

        if (isset($dirtyFields['is_active'])) {
            $changes['status'] = $dirtyFields['is_active'] ? 'Activated' : 'Deactivated';
        }

        if (isset($dirtyFields['credit_limit'])) {
            $changes['credit'] = "Credit limit updated to \${$dirtyFields['credit_limit']}";
        }

        if (isset($dirtyFields['price_list_id'])) {
            $changes['price_list'] = 'Price list assignment changed';
        }

        if (isset($dirtyFields['assigned_sales_rep'])) {
            $changes['assignment'] = 'Sales representative assignment changed';
        }

        if (isset($dirtyFields['payment_terms'])) {
            $changes['payment'] = "Payment terms updated to {$dirtyFields['payment_terms']} days";
        }

        // Log the update with changes
        $changeDescription = !empty($changes)
            ? ': ' . implode(', ', $changes)
            : '';

        ActivityLog::log(
            'customer_updated',
            "Customer '{$this->record->full_name}' was updated by " . Auth::user()->name . $changeDescription,
            Auth::user(),
            [
                'customer_code' => $this->record->code,
                'customer_id' => $this->record->id,
                'changes' => $dirtyFields,
            ]
        );
    }
}
