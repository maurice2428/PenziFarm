<?php

namespace App\Filament\Resources\BreedingBatchResource\Pages;

use App\Filament\Resources\BreedingBatchResource;
use App\Services\Breeding\BreedingBatchService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBreedingBatch extends CreateRecord
{
    protected static string $resource = BreedingBatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $femaleIds = array_values(array_filter($this->data['female_animal_ids'] ?? []));

        app(BreedingBatchService::class)->validateBatchSelections($data, $femaleIds);

        $data['total_females'] = count($femaleIds);
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $femaleIds = array_values(array_filter($this->data['female_animal_ids'] ?? []));

        app(BreedingBatchService::class)->createRecordsForBatch($this->record, $femaleIds);

        Notification::make()
            ->title('Breeding batch recorded')
            ->body('All selected females have been saved as breeding records under this batch.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}
