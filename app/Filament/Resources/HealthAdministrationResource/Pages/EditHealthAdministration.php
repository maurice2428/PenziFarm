<?php

namespace App\Filament\Resources\HealthAdministrationResource\Pages;

use App\Filament\Resources\HealthAdministrationResource;
use App\Models\HealthProduct;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditHealthAdministration extends EditRecord
{
    protected static string $resource = HealthAdministrationResource::class;

    protected array $selectedAnimalIds = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $animalIds = $this->record->animals()->pluck('animals.id')->map(fn ($id) => (string) $id)->all();

        $firstAnimal = $this->record->animals()->with('breed')->first();

        $data['animal_ids'] = $animalIds;
        $data['breed_id'] = $firstAnimal?->breed_id;
        $data['animal_selection_mode'] = 'specific_tags';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedAnimalIds = $data['animal_ids'] ?? [];

        $product = HealthProduct::find($data['health_product_id'] ?? null);
        $animalCount = count($this->selectedAnimalIds);

        if ($product) {
            $data['dosage_per_animal'] = (float) ($data['dosage_per_animal'] ?? $product->dosage_per_animal);
            $data['animal_count'] = $animalCount;
            $data['total_quantity_used'] = $animalCount * (float) $data['dosage_per_animal'];
            $data['next_due_date'] = $product->calculateNextDueDate($data['administered_at'] ?? now())
                ?->toDateString();
        }

        unset($data['breed_id'], $data['animal_selection_mode'], $data['animal_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->animals()->sync($this->selectedAnimalIds);

        Notification::make()
            ->title('Health administration updated')
            ->body('Animal selection and stock deduction were recalculated.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
