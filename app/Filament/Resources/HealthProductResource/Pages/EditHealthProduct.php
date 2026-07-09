<?php

namespace App\Filament\Resources\HealthProductResource\Pages;

use App\Filament\Resources\HealthProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHealthProduct extends EditRecord
{
    protected static string $resource = HealthProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
