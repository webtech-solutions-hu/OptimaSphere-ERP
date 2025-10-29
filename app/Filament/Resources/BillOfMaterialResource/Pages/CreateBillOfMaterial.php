<?php

namespace App\Filament\Resources\BillOfMaterialResource\Pages;

use App\Filament\Resources\BillOfMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillOfMaterial extends CreateRecord
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
