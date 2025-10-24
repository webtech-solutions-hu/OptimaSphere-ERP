<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\ActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $changes = [];
        $dirtyFields = $this->record->getChanges();

        if (isset($dirtyFields['name'])) {
            $changes['name'] = "Name changed to '{$dirtyFields['name']}'";
        }

        if (isset($dirtyFields['parent_id'])) {
            $changes['parent'] = 'Parent category changed';
        }

        if (isset($dirtyFields['is_active'])) {
            $changes['status'] = $dirtyFields['is_active'] ? 'Activated' : 'Deactivated';
        }

        $changeDescription = !empty($changes)
            ? ': ' . implode(', ', $changes)
            : '';

        ActivityLog::log(
            'category_updated',
            "Category '{$this->record->name}' was updated by " . Auth::user()->name . $changeDescription,
            Auth::user(),
            [
                'category_code' => $this->record->code,
                'category_id' => $this->record->id,
                'changes' => $dirtyFields,
            ]
        );
    }
}
