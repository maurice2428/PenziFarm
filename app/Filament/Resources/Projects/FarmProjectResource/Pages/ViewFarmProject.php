<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\Pages;

use App\Filament\Resources\Projects\FarmProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFarmProject extends ViewRecord
{
    protected static string $resource = FarmProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
