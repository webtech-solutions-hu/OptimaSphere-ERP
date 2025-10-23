<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ActivityLog;
use App\Notifications\AccountActivatedNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
        // Check if is_active was just changed from false to true
        if ($this->record->wasChanged('is_active') && $this->record->is_active) {
            ActivityLog::log(
                'account_activated',
                "{$this->record->name}'s account was activated",
                $this->record
            );
            $this->record->notify(new AccountActivatedNotification());
        }

        // Check if roles were changed
        if ($this->record->relationLoaded('roles') && $this->record->roles()->exists()) {
            $currentRoles = $this->record->roles->pluck('name')->toArray();
            ActivityLog::log(
                'role_changed',
                "{$this->record->name}'s roles were updated",
                $this->record,
                ['roles' => $currentRoles]
            );
        }
    }
}
