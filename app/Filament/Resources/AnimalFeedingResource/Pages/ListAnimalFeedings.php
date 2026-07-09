<?php

namespace App\Filament\Resources\AnimalFeedingResource\Pages;

use App\Filament\Resources\AnimalFeedingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimalFeedings extends ListRecords
{
    protected static string $resource = AnimalFeedingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Feeding')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => static::getResource()::canCreate()),
        ];
    }
}
