<?php

namespace App\Filament\Resources\AnimalClinicalCaseResource\Pages;

use App\Filament\Resources\AnimalClinicalCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnimalClinicalCase extends EditRecord
{
    protected static string $resource = AnimalClinicalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
