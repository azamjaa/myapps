<?php

namespace App\Filament\Resources\StafResource\Pages;

use App\Filament\Resources\StafResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStaf extends ViewRecord
{
    protected static string $resource = StafResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
