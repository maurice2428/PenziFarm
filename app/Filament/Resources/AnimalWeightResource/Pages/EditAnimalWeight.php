<?php

namespace App\Filament\Resources\AnimalWeightResource\Pages;

use App\Filament\Resources\AnimalWeightResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnimalWeight extends EditRecord
{
    protected static string $resource = AnimalWeightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
