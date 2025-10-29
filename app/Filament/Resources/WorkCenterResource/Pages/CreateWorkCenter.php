<?php

namespace App\Filament\Resources\WorkCenterResource\Pages;

use App\Filament\Resources\WorkCenterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkCenter extends CreateRecord
{
    protected static string $resource = WorkCenterResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
