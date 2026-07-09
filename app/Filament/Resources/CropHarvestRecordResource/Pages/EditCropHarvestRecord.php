<?php

namespace App\Filament\Resources\CropHarvestRecordResource\Pages;

use App\Filament\Resources\CropHarvestRecordResource;
use Filament\Resources\Pages\EditRecord;

class EditCropHarvestRecord extends EditRecord
{
    protected static string $resource = CropHarvestRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
