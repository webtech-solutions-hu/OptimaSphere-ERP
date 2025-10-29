<?php

namespace App\Filament\Resources\MaterialRequisitionResource\Pages;

use App\Filament\Resources\MaterialRequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialRequisitions extends ListRecords
{
    protected static string $resource = MaterialRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
