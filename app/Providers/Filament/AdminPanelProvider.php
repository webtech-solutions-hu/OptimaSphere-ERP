<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EditProfile;
use App\Filament\Widgets\CustomAccountWidget;
use App\Http\Middleware\EnsureUserIsActiveAndApproved;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->brandName('OptimaSphere ERP')
            ->colors([
                'primary' => Color::rgb('rgb(26, 78, 255)'), // Deep Tech Blue #1A4EFF
                'info' => Color::rgb('rgb(0, 224, 198)'),    // Aqua Cyan #00E0C6
                'success' => Color::rgb('rgb(16, 185, 129)'), // Green
                'warning' => Color::rgb('rgb(249, 115, 22)'), // Orange
                'danger' => Color::rgb('rgb(239, 68, 68)'),   // Red
                'gray' => Color::rgb('rgb(43, 47, 54)'),      // Graphite Gray #2B2F36

            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                CustomAccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsActiveAndApproved::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Edit Profile')
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ]);
    }
}
