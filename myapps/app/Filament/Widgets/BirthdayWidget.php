<?php

namespace App\Filament\Widgets;

use App\Models\Staf;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class BirthdayWidget extends Widget
{
    protected static string $view = 'filament.widgets.birthday-widget';
    protected static ?int $sort = 4;

    public function getBirthdayStaff(): Collection
    {
        $currentMonth = date('m');
        
        return Staf::whereRaw("SUBSTRING(no_kp, 3, 2) = ?", [$currentMonth])
            ->with(['bahagian', 'jawatan'])
            ->get()
            ->map(function ($staf) {
                // Extract birthday from IC
                $year = substr($staf->no_kp, 0, 2);
                $month = substr($staf->no_kp, 2, 2);
                $day = substr($staf->no_kp, 4, 2);
                
                // Determine century
                $fullYear = ((int)$year > 30) ? '19' . $year : '20' . $year;
                
                $staf->birthday_date = "$day/$month";
                $staf->birthday_full = "$day-$month-$fullYear";
                
                return $staf;
            })
            ->sortBy(function ($staf) {
                return (int)substr($staf->no_kp, 4, 2);
            });
    }
}

