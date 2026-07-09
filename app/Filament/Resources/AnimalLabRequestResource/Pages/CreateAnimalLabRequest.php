<?php

namespace App\Filament\Resources\AnimalLabRequestResource\Pages;

use App\Filament\Resources\AnimalLabRequestResource;
use App\Models\AnimalClinicalCase;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAnimalLabRequest extends CreateRecord
{
    protected static string $resource = AnimalLabRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
}
