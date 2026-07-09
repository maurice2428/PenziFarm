<?php

namespace App\Filament\Resources\CropHarvestRecordResource\Pages;

use App\Filament\Resources\CropHarvestRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCropHarvestRecord extends CreateRecord
{
    protected static string $resource = CropHarvestRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
