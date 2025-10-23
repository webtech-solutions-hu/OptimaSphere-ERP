<?php

namespace App\Filament\Resources\CompletedJobResource\Pages;

use App\Filament\Resources\CompletedJobResource;
use Filament\Resources\Pages\ListRecords;

class ListCompletedJobs extends ListRecords
{
    protected static string $resource = CompletedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
