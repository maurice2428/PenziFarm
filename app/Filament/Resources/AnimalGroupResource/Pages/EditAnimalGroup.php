<?php

namespace App\Filament\Resources\AnimalGroupResource\Pages;

use App\Filament\Resources\AnimalGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnimalGroup extends EditRecord
{
    protected static string $resource = AnimalGroupResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['animal_status'] = null;
        $data['animal_purpose'] = null;

        $data['animal_ids'] = $this->record
            ->activeMembers()
            ->pluck('animal_id')
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        AnimalGroupResource::syncGroupAnimals(
            $this->record,
            $this->form->getRawState()['animal_ids'] ?? []
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('delete animal groups') ?? false),
        ];
    }
}
