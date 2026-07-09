<?php

namespace App\Filament\Resources\Sales\IncomeCategoryResource\Pages;

use App\Filament\Resources\Sales\IncomeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncomeCategories extends ListRecords
{
    protected static string $resource = IncomeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
