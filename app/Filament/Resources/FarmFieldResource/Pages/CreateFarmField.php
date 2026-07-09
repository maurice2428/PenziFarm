<?php

namespace App\Filament\Resources\FarmFieldResource\Pages;

use App\Filament\Resources\FarmFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFarmField extends CreateRecord
{
    protected static string $resource = FarmFieldResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
