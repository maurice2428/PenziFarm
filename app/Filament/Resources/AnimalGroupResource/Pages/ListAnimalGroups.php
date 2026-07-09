<?php

namespace App\Filament\Resources\AnimalGroupResource\Pages;

use App\Filament\Resources\AnimalGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimalGroups extends ListRecords
{
    protected static string $resource = AnimalGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Animal Group'),
        ];
    }
}
