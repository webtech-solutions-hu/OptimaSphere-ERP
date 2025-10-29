<?php

namespace App\Filament\Resources\ProductionScheduleResource\Pages;

use App\Filament\Resources\ProductionScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionSchedule extends CreateRecord
{
    protected static string $resource = ProductionScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
