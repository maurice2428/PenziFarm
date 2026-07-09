<?php

namespace App\Filament\Resources\CropInputApplicationResource\Pages;

use App\Filament\Resources\CropInputApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCropInputApplications extends ListRecords
{
    protected static string $resource = CropInputApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Input Application')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
