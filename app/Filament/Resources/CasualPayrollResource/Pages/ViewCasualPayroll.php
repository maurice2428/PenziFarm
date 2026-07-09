<?php

namespace App\Filament\Resources\CasualPayrollResource\Pages;

use App\Filament\Resources\CasualPayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCasualPayroll extends ViewRecord
{
    protected static string $resource = CasualPayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->visible(fn () => auth()->user()?->can('export casual payroll'))
                ->url(fn () => route('casual-payroll.report', $this->record))
                ->openUrlInNewTab(),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('edit casual payroll')),
        ];
    }
}
