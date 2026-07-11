<?php
namespace App\Filament\Resources\Accounting\ExpenseCategoryResource\Pages;
use App\Filament\Resources\Accounting\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->slideOver()->modalWidth('5xl')];
    }
}
