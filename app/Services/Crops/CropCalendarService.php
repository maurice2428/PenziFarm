<?php

namespace App\Services\Crops;

use App\Models\CropCatalog;
use Carbon\Carbon;

class CropCalendarService
{
    public function datesFor(CropCatalog $crop, string $baseDate): array
    {
        $date = Carbon::parse($baseDate);

        return [
            'expected_germination_from' => $crop->germination_days_min
                ? $date->copy()->addDays((int) $crop->germination_days_min)->toDateString()
                : null,
            'expected_germination_to' => $crop->germination_days_max
                ? $date->copy()->addDays((int) $crop->germination_days_max)->toDateString()
                : null,
            'expected_transplant_date' => $crop->transplant_days
                ? $date->copy()->addDays((int) $crop->transplant_days)->toDateString()
                : null,
            'expected_harvest_from' => $crop->maturity_days_min
                ? $date->copy()->addDays((int) $crop->maturity_days_min)->toDateString()
                : null,
            'expected_harvest_to' => $crop->maturity_days_max
                ? $date->copy()->addDays((int) $crop->maturity_days_max)->toDateString()
                : null,
        ];
    }
}
