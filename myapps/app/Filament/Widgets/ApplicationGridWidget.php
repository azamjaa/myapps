<?php

namespace App\Filament\Widgets;

use App\Models\Aplikasi;
use Filament\Widgets\Widget;

class ApplicationGridWidget extends Widget
{
    protected static string $view = 'filament.widgets.application-grid-widget';
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public function getApplications()
    {
        return Aplikasi::with('kategori')
            ->where('status', 1)
            ->orderBy('nama_aplikasi')
            ->get();
    }
}

