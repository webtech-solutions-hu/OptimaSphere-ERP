<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

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
        if (isset($dirtyFields['is_approved']) && $dirtyFields['is_approved']) {
            $changes['approved'] = 'Supplier approved';
        }

        if (isset($dirtyFields['is_active'])) {
            $changes['status'] = $dirtyFields['is_active'] ? 'Activated' : 'Deactivated';
        }

        if (isset($dirtyFields['performance_rating'])) {
            $changes['rating'] = "Performance rating updated to {$dirtyFields['performance_rating']}";
        }

        if (isset($dirtyFields['contract_number'])) {
            $changes['contract'] = 'Contract details updated';
        }

        if (isset($dirtyFields['assigned_procurement_officer'])) {
            $changes['assignment'] = 'Procurement officer assignment changed';
        }

        // Log the update with changes
        $changeDescription = !empty($changes)
            ? ': ' . implode(', ', $changes)
            : '';

        ActivityLog::log(
            'supplier_updated',
            "Supplier '{$this->record->company_name}' was updated by " . Auth::user()->name . $changeDescription,
            Auth::user(),
            [
                'supplier_code' => $this->record->code,
                'supplier_id' => $this->record->id,
                'changes' => $dirtyFields,
            ]
        );
    }
}
