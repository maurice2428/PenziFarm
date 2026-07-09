<?php

namespace App\Filament\Resources\VeterinaryClinicResource\Pages;

use App\Filament\Resources\VeterinaryClinicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVeterinaryClinic extends EditRecord
{
    protected static string $resource = VeterinaryClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
