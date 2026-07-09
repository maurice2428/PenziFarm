<?php

namespace App\Filament\Resources\Projects\ProjectCategoryResource\Pages;

use App\Filament\Resources\Projects\ProjectCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectCategories extends ListRecords
{
    protected static string $resource = ProjectCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Category')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
