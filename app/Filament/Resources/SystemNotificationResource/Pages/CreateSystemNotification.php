<?php

namespace App\Filament\Resources\SystemNotificationResource\Pages;

use App\Filament\Resources\SystemNotificationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSystemNotification extends CreateRecord
{
    protected static string $resource = SystemNotificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        // If status is pending, send immediately
        if ($data['status'] === 'pending') {
            $data['sent_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Send notification if status is pending
        if ($this->record->status === 'pending') {
            $this->record->send();
        }
    }
}
