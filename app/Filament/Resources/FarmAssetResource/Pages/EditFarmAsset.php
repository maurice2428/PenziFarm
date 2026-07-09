<?php

namespace App\Filament\Resources\FarmAssetResource\Pages;

use App\Filament\Resources\FarmAssetResource;
use Filament\Resources\Pages\EditRecord;

class EditFarmAsset extends EditRecord
{
    protected static string $resource = FarmAssetResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
