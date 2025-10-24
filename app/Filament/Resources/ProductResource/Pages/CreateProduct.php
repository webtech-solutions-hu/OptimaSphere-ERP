<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        ActivityLog::log(
            'product_created',
            "New product '{$this->record->name}' was created by " . Auth::user()->name,
            Auth::user(),
            [
                'product_code' => $this->record->code,
                'product_id' => $this->record->id,
                'sku' => $this->record->sku,
                'product_type' => $this->record->type,
            ]
        );
    }
}
