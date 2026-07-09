<?php

namespace App\Filament\Resources\AnimalTransferResource\Pages;

use App\Filament\Resources\AnimalTransferResource;
use App\Models\Animal;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnimalTransfer extends EditRecord
{
    protected static string $resource = AnimalTransferResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $animalIds = $this->record
            ->items()
            ->pluck('animal_id')
            ->toArray();

        $data['animal_ids'] = $animalIds;

        $data['breed_id'] = Animal::query()
            ->whereIn('id', $animalIds)
            ->value('breed_id');

        return $data;
    }

    protected function afterSave(): void
    {
        AnimalTransferResource::syncTransferAnimals(
            $this->record,
            $this->form->getRawState()['animal_ids'] ?? []
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('delete animal transfers') ?? false),
        ];
    }
}
