<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EditProfile;
use Filament\Widgets\Widget;

class CustomAccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.custom-account-widget';

    public function getProfileUrl(): string
    {
        return EditProfile::getUrl();
    }
}
