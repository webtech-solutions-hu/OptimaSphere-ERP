<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Notifications\UserNeedsApprovalNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Send email verification notification (built-in Laravel)
        if (!$this->record->isEmailVerified()) {
            $this->record->sendEmailVerificationNotification();
        }

        // Notify all users who can approve about the new registration
        $approvers = User::whereHas('roles', function ($query) {
            $query->where('supervisor', true)
                ->orWhereIn('slug', ['power-user', 'it']);
        })->get();

        Notification::send($approvers, new UserNeedsApprovalNotification($this->record));
    }
}
