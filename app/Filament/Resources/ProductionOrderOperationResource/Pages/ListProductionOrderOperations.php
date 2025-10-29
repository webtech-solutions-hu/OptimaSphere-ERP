<?php

namespace App\Filament\Resources\ProductionOrderOperationResource\Pages;

use App\Filament\Resources\ProductionOrderOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionOrderOperations extends ListRecords
{
    protected static string $resource = ProductionOrderOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
