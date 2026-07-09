<?php

namespace App\Filament\Resources\AnimalFeedingResource\Pages;

use App\Filament\Resources\AnimalFeedingResource;
use App\Services\Feeding\AnimalFeedingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAnimalFeeding extends CreateRecord
{
    protected static string $resource = AnimalFeedingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AnimalFeedingService::class)->record([
            ...$data,
            'animal_ids' => $this->data['animal_ids'] ?? [],
            'items' => $this->data['items'] ?? [],
        ]);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Feeding recorded')
            ->body('Feed quantities have been deducted from stock through stock movements.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
