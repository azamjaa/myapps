<?php

namespace App\Filament\Widgets;

use App\Models\Aplikasi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class AplikasiCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Aplikasi Mengikut Kategori';
    protected static ?int $sort = 3;
    
    // Reduce polling to improve performance
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        // Cache chart data for 5 minutes
        $categoryCounts = Cache::remember('aplikasi_category_chart', 300, function () {
            return Aplikasi::selectRaw('kategori.nama_kategori, COUNT(*) as count')
                ->join('kategori', 'aplikasi.id_kategori', '=', 'kategori.id_kategori')
                ->groupBy('kategori.nama_kategori', 'kategori.id_kategori')
                ->pluck('count', 'kategori.nama_kategori')
                ->toArray();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Aplikasi',
                    'data' => array_values($categoryCounts),
                    'backgroundColor' => [
                        'rgba(30, 58, 138, 0.85)',  // Navy Blue - Dalaman
                        'rgba(251, 191, 36, 0.85)', // Gold - Luaran
                        'rgba(59, 130, 246, 0.85)', // Light Blue - Gunasama
                    ],
                    'borderColor' => [
                        'rgb(30, 58, 138)',
                        'rgb(251, 191, 36)',
                        'rgb(59, 130, 246)',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => array_keys($categoryCounts),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'font' => [
                            'size' => 13,
                            'weight' => '600',
                        ],
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => true,
        ];
    }
}

