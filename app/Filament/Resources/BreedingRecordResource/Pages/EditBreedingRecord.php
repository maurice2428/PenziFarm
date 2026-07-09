<?php

namespace App\Filament\Resources\BreedingRecordResource\Pages;

use App\Filament\Resources\BreedingRecordResource;
use Filament\Resources\Pages\EditRecord;

class EditBreedingRecord extends EditRecord
{
    protected static string $resource = BreedingRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
