<?php

namespace App\Filament\Resources\NurseryBatchResource\Pages;

use App\Filament\Resources\NurseryBatchResource;
use Filament\Resources\Pages\EditRecord;

class EditNurseryBatch extends EditRecord
{
    protected static string $resource = NurseryBatchResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
