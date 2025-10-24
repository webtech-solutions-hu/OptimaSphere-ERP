<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'unit_created',
            "New unit '{$this->record->name}' ({$this->record->code}) was created by " . Auth::user()->name,
            Auth::user(),
            [
                'unit_code' => $this->record->code,
                'unit_id' => $this->record->id,
            ]
        );
    }
}
