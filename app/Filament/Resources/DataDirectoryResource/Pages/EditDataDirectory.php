<?php

namespace App\Filament\Resources\DataDirectoryResource\Pages;

use App\Filament\Resources\DataDirectoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataDirectory extends EditRecord
{
    protected static string $resource = DataDirectoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
