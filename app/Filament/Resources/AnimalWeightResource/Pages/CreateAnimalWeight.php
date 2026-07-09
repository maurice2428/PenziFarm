<?php

namespace App\Filament\Resources\AnimalWeightResource\Pages;

use App\Filament\Resources\AnimalWeightResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnimalWeight extends CreateRecord
{
    protected static string $resource = AnimalWeightResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        return $data;
    }
}
