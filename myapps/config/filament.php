<?php

return [
    'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
    'pages' => [
        'namespace' => 'App\\Filament\\Pages',
        'path' => app_path('Filament/Pages'),
        'register' => [
            //
        ],
    ],
    'resources' => [
        'namespace' => 'App\\Filament\\Resources',
        'path' => app_path('Filament/Resources'),
        'register' => [
            //
        ],
    ],
    'widgets' => [
        'namespace' => 'App\\Filament\\Widgets',
        'path' => app_path('Filament/Widgets'),
        'register' => [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\StafByStatusChart::class,
            \App\Filament\Widgets\AplikasiByKategoriChart::class,
            \App\Filament\Widgets\BirthdayWidget::class,
        ],
    ],
    'livewire' => [
        'namespace' => 'App\\Filament',
        'path' => app_path('Filament'),
    ],
    'dark_mode' => false,
    'database_notifications' => [
        'enabled' => false,
    ],
    'broadcasting' => [
        'echo' => [
            'broadcaster' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => true,
        ],
    ],
    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DRIVER', 'public'),
    'google_fonts' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    'middleware' => [
        'auth' => [
            \Filament\Http\Middleware\Authenticate::class,
        ],
        'base' => [
            \Filament\Http\Middleware\DispatchServingFilamentEvent::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Filament\Http\Middleware\SetUpPanel::class,
        ],
    ],
];

