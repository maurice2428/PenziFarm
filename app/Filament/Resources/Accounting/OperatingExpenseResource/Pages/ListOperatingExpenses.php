<?php
namespace App\Filament\Resources\Accounting\OperatingExpenseResource\Pages;
use App\Filament\Resources\Accounting\OperatingExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOperatingExpenses extends ListRecords
{
    protected static string $resource = OperatingExpenseResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
