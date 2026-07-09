<?php

namespace App\Filament\Resources\FarmAssetResource\Pages;

use App\Filament\Resources\FarmAssetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFarmAsset extends CreateRecord
{
    protected static string $resource = FarmAssetResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
