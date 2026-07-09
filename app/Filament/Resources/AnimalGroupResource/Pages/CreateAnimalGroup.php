<?php

namespace App\Filament\Resources\AnimalGroupResource\Pages;

use App\Filament\Resources\AnimalGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnimalGroup extends CreateRecord
{
    protected static string $resource = AnimalGroupResource::class;

    protected function afterCreate(): void
    {
        AnimalGroupResource::syncGroupAnimals(
            $this->record,
            $this->form->getRawState()['animal_ids'] ?? []
        );
    }
}
