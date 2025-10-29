<?php

namespace App\Filament\Resources\BillOfMaterialResource\Pages;

use App\Filament\Resources\BillOfMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillOfMaterial extends EditRecord
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->status === 'draft'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
