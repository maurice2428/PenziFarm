<?php

namespace App\Filament\Resources\DataDirectoryResource\Pages;

use App\Filament\Resources\DataDirectoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDataDirectory extends CreateRecord
{
    protected static string $resource = DataDirectoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
