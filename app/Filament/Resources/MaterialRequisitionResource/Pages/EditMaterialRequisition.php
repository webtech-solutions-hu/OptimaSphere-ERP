<?php

namespace App\Filament\Resources\MaterialRequisitionResource\Pages;

use App\Filament\Resources\MaterialRequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialRequisition extends EditRecord
{
    protected static string $resource = MaterialRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
