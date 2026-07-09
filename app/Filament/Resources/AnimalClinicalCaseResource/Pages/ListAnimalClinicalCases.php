<?php

namespace App\Filament\Resources\AnimalClinicalCaseResource\Pages;

use App\Filament\Resources\AnimalClinicalCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimalClinicalCases extends ListRecords
{
    protected static string $resource = AnimalClinicalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Open Sick Case'),
        ];
    }
}
