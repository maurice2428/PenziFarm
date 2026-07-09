<?php

namespace App\Filament\Resources\FarmFieldResource\Pages;

use App\Filament\Resources\FarmFieldResource;
use Filament\Resources\Pages\EditRecord;

class EditFarmField extends EditRecord
{
    protected static string $resource = FarmFieldResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
