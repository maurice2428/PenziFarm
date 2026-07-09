<?php

namespace App\Filament\Resources\Projects\ProjectCategoryResource\Pages;

use App\Filament\Resources\Projects\ProjectCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectCategory extends ViewRecord
{
    protected static string $resource = ProjectCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
