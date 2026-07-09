<?php

namespace Tests\Unit;

use App\Models\BreedingRecord;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BreedingRecordGestationTest extends TestCase
{
    public function test_minimum_delivery_date_uses_mating_date_plus_gestation_days(): void
    {
        $record = new BreedingRecord([
            'mating_date' => '2026-01-01',
            'gestation_days' => 150,
        ]);

        $this->assertSame(
            '2026-05-31',
            $record->minimumDeliveryDate()?->toDateString(),
        );
    }

    public function test_delivery_before_minimum_gestation_is_rejected(): void
    {
        $record = new BreedingRecord([
            'mating_date' => '2026-01-01',
            'gestation_days' => 150,
        ]);

        $this->expectException(ValidationException::class);

        $record->assertDeliveryDateMeetsGestation('2026-05-30');
    }
}
