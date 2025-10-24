<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'supplier_created',
            "New supplier '{$this->record->company_name}' was created by " . Auth::user()->name,
            Auth::user(),
            [
                'supplier_code' => $this->record->code,
                'supplier_id' => $this->record->id,
                'supplier_type' => $this->record->type,
            ]
        );
    }
}
