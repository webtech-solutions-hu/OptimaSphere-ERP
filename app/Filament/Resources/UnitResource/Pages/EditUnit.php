<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

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

        if (isset($dirtyFields['conversion_factor'])) {
            $changes['conversion'] = "Conversion factor changed to {$dirtyFields['conversion_factor']}";
        }

        if (isset($dirtyFields['is_active'])) {
            $changes['status'] = $dirtyFields['is_active'] ? 'Activated' : 'Deactivated';
        }

        $changeDescription = !empty($changes)
            ? ': ' . implode(', ', $changes)
            : '';

        ActivityLog::log(
            'unit_updated',
            "Unit '{$this->record->name}' was updated by " . Auth::user()->name . $changeDescription,
            Auth::user(),
            [
                'unit_code' => $this->record->code,
                'unit_id' => $this->record->id,
                'changes' => $dirtyFields,
            ]
        );
    }
}
