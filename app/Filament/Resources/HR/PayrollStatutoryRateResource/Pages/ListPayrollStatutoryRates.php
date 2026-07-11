<?php
namespace App\Filament\Resources\HR\PayrollStatutoryRateResource\Pages;
use App\Filament\Resources\HR\PayrollStatutoryRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListPayrollStatutoryRates extends ListRecords
{
    protected static string $resource = PayrollStatutoryRateResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->slideOver()->modalWidth('6xl')];
    }
}
