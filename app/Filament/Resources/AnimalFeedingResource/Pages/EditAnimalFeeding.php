<?php

namespace App\Filament\Resources\AnimalFeedingResource\Pages;

use App\Filament\Resources\AnimalFeedingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnimalFeeding extends EditRecord
{
    protected static string $resource = AnimalFeedingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
