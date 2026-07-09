<?php

namespace App\Filament\Resources\VeterinaryClinicResource\Pages;

use App\Filament\Resources\VeterinaryClinicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVeterinaryClinics extends ListRecords
{
    protected static string $resource = VeterinaryClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Clinic / Laboratory'),
        ];
    }
}
