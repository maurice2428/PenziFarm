<?php

namespace App\Filament\Resources\CropCatalogResource\Pages;

use App\Filament\Resources\CropCatalogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCropCatalog extends CreateRecord
{
    protected static string $resource = CropCatalogResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
