<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'customer_created',
            "New customer '{$this->record->full_name}' was created by " . Auth::user()->name,
            Auth::user(),
            [
                'customer_code' => $this->record->code,
                'customer_id' => $this->record->id,
                'customer_type' => $this->record->type,
            ]
        );
    }
}
