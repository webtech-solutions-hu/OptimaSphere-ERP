<?php

namespace App\Filament\Resources\ProductionScheduleResource\Pages;

use App\Filament\Resources\ProductionScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionSchedules extends ListRecords
{
    protected static string $resource = ProductionScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
