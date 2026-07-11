<?php

namespace App\Filament\Resources\AnimalWeightResource\Pages;

use App\Filament\Resources\AnimalWeightResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAnimalWeight extends ViewRecord
{
    protected static string $resource = AnimalWeightResource::class;

    protected static string $view = 'filament.resources.animal-weight-resource.pages.view-animal-weight';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Latest Weight')
                ->icon('heroicon-o-pencil-square'),

            Actions\Action::make('animal')
                ->label('Open Animal Record')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->url(fn () => \App\Filament\Resources\AnimalResource::getUrl('edit', [
                    'record' => $this->record->animal_id,
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
