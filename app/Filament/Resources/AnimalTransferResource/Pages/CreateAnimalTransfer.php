<?php

namespace App\Filament\Resources\AnimalTransferResource\Pages;

use App\Filament\Resources\AnimalTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnimalTransfer extends CreateRecord
{
    protected static string $resource = AnimalTransferResource::class;

    protected function afterCreate(): void
    {
        AnimalTransferResource::syncTransferAnimals(
            $this->record,
            $this->form->getRawState()['animal_ids'] ?? []
        );
    }
}
