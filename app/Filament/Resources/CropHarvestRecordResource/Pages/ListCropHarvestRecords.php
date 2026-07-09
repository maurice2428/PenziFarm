<?php

namespace App\Filament\Resources\CropHarvestRecordResource\Pages;

use App\Filament\Resources\CropHarvestRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCropHarvestRecords extends ListRecords
{
    protected static string $resource = CropHarvestRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Harvest Record')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
