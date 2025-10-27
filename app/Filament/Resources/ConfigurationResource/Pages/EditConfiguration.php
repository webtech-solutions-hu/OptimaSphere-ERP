<?php

namespace App\Filament\Resources\ConfigurationResource\Pages;

use App\Filament\Resources\ConfigurationResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfiguration extends EditRecord
{
    protected static string $resource = ConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure the value field shows correctly based on type
        // The Toggle component might be converting it, so we just pass it through
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert boolean toggle value back to string for storage
        if ($data['type'] === 'boolean' && isset($data['value'])) {
            $data['value'] = $data['value'] ? '1' : '0';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Clear settings cache after updating a setting
        Setting::clearCache();
    }
}
