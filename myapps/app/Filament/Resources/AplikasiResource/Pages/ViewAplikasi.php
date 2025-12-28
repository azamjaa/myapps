<?php

namespace App\Filament\Resources\AplikasiResource\Pages;

use App\Filament\Resources\AplikasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAplikasi extends ViewRecord
{
    protected static string $resource = AplikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
