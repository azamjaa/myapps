<?php

namespace App\Filament\Widgets;

use App\Models\Aplikasi;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class ApplicationGridWidget extends Widget
{
    protected static string $view = 'filament.widgets.application-grid-widget';
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    // Reduce polling to improve performance
    protected static ?string $pollingInterval = '60s';

    public function getApplications()
    {
        // Cache applications for 10 minutes
        return Cache::remember('dashboard_applications', 600, function () {
            return Aplikasi::with('kategori')
                ->where('status', 1)
                ->orderBy('nama_aplikasi')
                ->get();
        });
    }
}

