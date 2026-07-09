<?php

namespace App\Filament\Resources\AnimalLabRequestResource\Pages;

use App\Filament\Resources\AnimalLabRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimalLabRequests extends ListRecords
{
    protected static string $resource = AnimalLabRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Lab Request'),
        ];
    }
}
