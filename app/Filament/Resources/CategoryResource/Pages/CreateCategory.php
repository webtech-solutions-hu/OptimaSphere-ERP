<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'category_created',
            "New category '{$this->record->name}' was created by " . Auth::user()->name,
            Auth::user(),
            [
                'category_code' => $this->record->code,
                'category_id' => $this->record->id,
            ]
        );
    }
}
