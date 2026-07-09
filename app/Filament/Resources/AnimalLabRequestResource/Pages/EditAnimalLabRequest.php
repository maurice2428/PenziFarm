<?php

namespace App\Filament\Resources\AnimalLabRequestResource\Pages;

use App\Filament\Resources\AnimalLabRequestResource;
use App\Models\AnimalClinicalCase;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAnimalLabRequest extends EditRecord
{
    protected static string $resource = AnimalLabRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['clinical_case_id'] ?? null)) {
            $case = AnimalClinicalCase::find($data['clinical_case_id']);

            if (! $case) {
                throw ValidationException::withMessages([
                    'clinical_case_id' => 'The selected sick case no longer exists.',
                ]);
            }

            $data['animal_id'] = $case->animal_id;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
