<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
use App\Filament\Pages\Auth\Login;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->emailVerification()
            ->colors([
                'primary' => [
                    50 => '240, 249, 255',
                    100 => '224, 242, 254',
                    200 => '186, 230, 253',
                    300 => '125, 211, 252',
                    400 => '56, 189, 248',
                    500 => '14, 165, 233',
                    600 => '2, 132, 199',
                    700 => '3, 105, 161',
                    800 => '7, 89, 133',
                    900 => '30, 58, 138', // Navy Blue - KEDA Primary
                    950 => '12, 74, 110',
                ],
                'warning' => Color::Amber, // Gold - KEDA Secondary
                'success' => Color::Emerald,
                'danger' => Color::Red,
                'info' => Color::Sky,
            ])
            ->brandName('MyApps KEDA')
            ->brandLogo(asset('images/keda-logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\ApplicationGridWidget::class,
                \App\Filament\Widgets\StafStatusChart::class,
                \App\Filament\Widgets\AplikasiCategoryChart::class,
                \App\Filament\Widgets\BirthdayWidget::class,
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
                \App\Http\Middleware\CheckAdminRole::class,
            ]);
    }
}
