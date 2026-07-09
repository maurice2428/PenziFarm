<?php

namespace App\Filament\Resources\CropInputApplicationResource\Pages;

use App\Filament\Resources\CropInputApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCropInputApplication extends EditRecord
{
    protected static string $resource = CropInputApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
