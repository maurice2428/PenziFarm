<?php

namespace App\Filament\Resources\CropCatalogResource\Pages;

use App\Filament\Resources\CropCatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCropCatalogs extends ListRecords
{
    protected static string $resource = CropCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Crop')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
