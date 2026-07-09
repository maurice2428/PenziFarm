<?php

namespace App\Filament\Resources\AnimalHealthRecordResource\Pages;

use App\Filament\Resources\AnimalHealthRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnimalHealthRecord extends EditRecord
{
    protected static string $resource = AnimalHealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
