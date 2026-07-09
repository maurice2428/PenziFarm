<?php

namespace App\Filament\Resources\HealthAdministrationResource\Pages;

use App\Filament\Resources\HealthAdministrationResource;
use App\Models\HealthProduct;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CreateHealthAdministration extends CreateRecord
{
    protected static string $resource = HealthAdministrationResource::class;

    /**
     * Stores selected tags temporarily because animal_ids is not a
     * health_administrations database column.
     */
    protected array $selectedAnimalIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $animalIds = $data['animal_ids'] ?? [];

        $this->selectedAnimalIds = collect(
            is_array($animalIds) ? $animalIds : [$animalIds]
        )
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (count($this->selectedAnimalIds) === 0) {
            throw ValidationException::withMessages([
                'animal_ids' => 'Select at least one animal before saving this health administration.',
            ]);
        }

        $product = HealthProduct::query()->find(
            $data['health_product_id'] ?? null
        );

        if (!$product) {
            throw ValidationException::withMessages([
                'health_product_id' => 'Select a valid health product.',
            ]);
        }

        $administeredAt = $data['administered_at'] ?? now('Africa/Nairobi');

        $dosagePerAnimal = (float) (
            $data['dosage_per_animal']
                ?? $product->dosage_per_animal
                ?? 0
        );

        $animalCount = count($this->selectedAnimalIds);

        $data['administered_at'] = $administeredAt;
        $data['animal_count'] = $animalCount;
        $data['dosage_per_animal'] = $dosagePerAnimal;
        $data['total_quantity_used'] = $animalCount * $dosagePerAnimal;

        $data['next_due_date'] = $product
            ->calculateNextDueDate($administeredAt)
            ?->toDateString();

        /*
         * These fields are used only by Filament. They must never be sent
         * to the health_administrations table.
         */
        unset(
            $data['animal_ids'],
            $data['breed_id'],
            $data['animal_selection_mode']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        /*
         * This is the missing piece.
         * It inserts one row per selected animal into:
         * health_administration_animals
         */
        $this->record->animals()->sync($this->selectedAnimalIds);

        $this->record->refresh();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Health administration recorded successfully';
    }

    protected function getCreatedNotificationBody(): ?string
    {
        return count($this->selectedAnimalIds)
            . ' animal(s) were linked to this administration record.';
    }
}
