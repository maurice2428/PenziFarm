<?php

namespace App\Filament\Resources\DataDirectoryResource\Pages;

use App\Filament\Resources\DataDirectoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataDirectories extends ListRecords
{
    protected static string $resource = DataDirectoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
