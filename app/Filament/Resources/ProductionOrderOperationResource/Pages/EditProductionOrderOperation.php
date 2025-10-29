<?php

namespace App\Filament\Resources\ProductionOrderOperationResource\Pages;

use App\Filament\Resources\ProductionOrderOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionOrderOperation extends EditRecord
{
    protected static string $resource = ProductionOrderOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
