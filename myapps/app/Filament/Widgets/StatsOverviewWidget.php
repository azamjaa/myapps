<?php

namespace App\Filament\Widgets;

use App\Models\Staf;
use App\Models\Aplikasi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    
    protected function getStats(): array
    {
        $totalStaf = Staf::count();
        $activeStaf = Staf::where('id_status', 1)->count();
        $totalApps = Aplikasi::count();
        $ssoApps = Aplikasi::where('sso_comply', 1)->count();
        
        // Calculate trends (mock data for demo - replace with real historical data)
        $activePercentage = $totalStaf > 0 ? round(($activeStaf / $totalStaf) * 100) : 0;
        $ssoPercentage = $totalApps > 0 ? round(($ssoApps / $totalApps) * 100) : 0;
        
        return [
            Stat::make('👥 Jumlah Staf', number_format($totalStaf))
                ->description("$activePercentage% Staf Aktif")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([15, 20, 18, 22, 25, 23, 28, $totalStaf])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-blue-900 to-blue-700 dark:from-blue-900 dark:to-blue-700',
                ]),
            
            Stat::make('✅ Staf Aktif', number_format($activeStaf))
                ->description('Masih berkhidmat')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([12, 15, 14, 18, 20, 19, 22, $activeStaf]),
            
            Stat::make('📱 Jumlah Aplikasi', number_format($totalApps))
                ->description('Total aplikasi sistem')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('warning')
                ->chart([3, 4, 5, 6, 7, 8, 9, $totalApps])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-amber-500 to-yellow-600 dark:from-amber-600 dark:to-yellow-700',
                ]),
            
            Stat::make('🔐 SSO Comply', number_format($ssoApps))
                ->description("$ssoPercentage% Mematuhi SSO")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success')
                ->chart([2, 3, 4, 5, 6, 7, 8, $ssoApps]),
        ];
    }
}

