<?php

namespace App\Filament\Resources\AnimalHealthRecordResource\Pages;

use App\Filament\Resources\AnimalHealthRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimalHealthRecords extends ListRecords
{
    protected static string $resource = AnimalHealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
