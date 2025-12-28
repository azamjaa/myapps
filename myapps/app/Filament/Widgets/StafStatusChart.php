<?php

namespace App\Filament\Widgets;

use App\Models\Staf;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class StafStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Pecahan Staf Mengikut Status';
    protected static ?int $sort = 2;
    
    // Reduce polling to improve performance
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        // Cache chart data for 5 minutes
        $statusCounts = Cache::remember('staf_status_chart', 300, function () {
            return Staf::selectRaw('status.status, COUNT(*) as count')
                ->join('status', 'staf.id_status', '=', 'status.id_status')
                ->groupBy('status.status', 'status.id_status')
                ->pluck('count', 'status.status')
                ->toArray();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Staf',
                    'data' => array_values($statusCounts),
                    'backgroundColor' => [
                        'rgba(30, 58, 138, 0.85)',  // Navy Blue - Masih Bekerja
                        'rgba(251, 191, 36, 0.85)', // Gold - Bersara
                        'rgba(239, 68, 68, 0.85)',  // Red - Berhenti
                    ],
                    'borderColor' => [
                        'rgb(30, 58, 138)',
                        'rgb(251, 191, 36)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 3,
                    'hoverOffset' => 20,
                ],
            ],
            'labels' => array_keys($statusCounts),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'font' => [
                            'size' => 13,
                            'weight' => '600',
                        ],
                        'padding' => 15,
                        'usePointStyle' => true,
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(30, 58, 138, 0.95)',
                    'titleFont' => [
                        'size' => 16,
                        'weight' => 'bold',
                    ],
                    'bodyFont' => [
                        'size' => 14,
                    ],
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => true,
        ];
    }
}

