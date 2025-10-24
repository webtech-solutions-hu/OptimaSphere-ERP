<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Home extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.home';

    protected static ?string $title = 'Command Center';

    protected static ?int $navigationSort = -1;

    protected static ?string $navigationLabel = 'Home';

    public function getHeading(): string
    {
        $user = auth()->user();
        $greeting = $this->getGreeting();

        return "{$greeting}, {$user->name}";
    }

    public function getSubheading(): ?string
    {
        return "Here's what's performing best today.";
    }

    protected function getGreeting(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomeWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\QuickStatsWidget::class,
        ];
    }
}
