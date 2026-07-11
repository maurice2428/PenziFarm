<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\PayrollStatutoryRate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class PayrollStatutoryRateService
{
    public function effective(
        string $type,
        CarbonInterface|string|null $date = null
    ): ?PayrollStatutoryRate {
        $date = $date
            ? Carbon::parse($date)
            : now('Africa/Nairobi');

        return PayrollStatutoryRate::query()
            ->effectiveOn($date->toDateString())
            ->where('type', $type)
            ->latest('effective_from')
            ->first();
    }

    public function dueDate(
        string $type,
        CarbonInterface|string $periodEnd
    ): CarbonInterface {
        $periodEnd = Carbon::parse($periodEnd);
        $rate = $this->effective($type, $periodEnd);
        $day = max(1, (int) ($rate?->remittance_due_day ?? 9));

        if (in_array($type, ['housing_levy', 'ahl'], true)) {
            /*
             * AHL is due within nine working days after month-end.
             * This excludes weekends. Confirm public holidays before filing.
             */
            $date = $periodEnd->copy()->endOfMonth();
            $workingDays = 0;

            while ($workingDays < 9) {
                $date->addDay();

                if (! $date->isWeekend()) {
                    $workingDays++;
                }
            }

            return $date->startOfDay();
        }

        $nextMonth = $periodEnd->copy()->addMonthNoOverflow();

        return $nextMonth
            ->day(min($day, $nextMonth->daysInMonth))
            ->startOfDay();
    }
}
