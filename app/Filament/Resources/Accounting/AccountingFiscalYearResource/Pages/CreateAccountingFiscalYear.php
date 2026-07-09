<?php

namespace App\Filament\Resources\Accounting\AccountingFiscalYearResource\Pages;

use App\Filament\Resources\Accounting\AccountingFiscalYearResource;
use App\Models\Accounting\AccountingPeriod;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountingFiscalYear extends CreateRecord
{
    protected static string $resource = AccountingFiscalYearResource::class;

    protected function afterCreate(): void
    {
        $start = Carbon::parse($this->record->start_date)->startOfMonth();
        $end = Carbon::parse($this->record->end_date)->endOfMonth();
        $cursor = $start->copy();
        $periodNumber = 1;

        while ($cursor->lte($end) && $periodNumber <= 12) {
            AccountingPeriod::updateOrCreate(
                ['fiscal_year_id' => $this->record->id, 'period_number' => $periodNumber],
                [
                    'name' => $cursor->format('F Y'),
                    'start_date' => $cursor->copy()->startOfMonth()->toDateString(),
                    'end_date' => $cursor->copy()->endOfMonth()->toDateString(),
                    'status' => 'open',
                ]
            );

            $cursor->addMonth();
            $periodNumber++;
        }
    }
}
