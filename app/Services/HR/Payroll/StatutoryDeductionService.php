<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\Employee;
use App\Models\HR\PayrollStatutoryRate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class StatutoryDeductionService
{
    public function __construct(
        private readonly PayrollStatutoryRateService $rates
    ) {
    }

    public function calculate(
        float $grossPay,
        Employee $employee,
        CarbonInterface|string|null $effectiveDate = null
    ): array {
        $grossPay = max(0, $grossPay);
        $effectiveDate = $effectiveDate
            ? Carbon::parse($effectiveDate)
            : now('Africa/Nairobi');

        $nssfRate = $this->rates->effective('nssf', $effectiveDate);
        $shifRate = $this->rates->effective('shif', $effectiveDate);
        $housingRate = $this->rates->effective(
            'housing_levy',
            $effectiveDate
        );
        $payeRate = $this->rates->effective('paye', $effectiveDate);

        $nssfEmployee = $employee->nssf_enabled
            ? $this->calculateNssf($grossPay, $nssfRate, 'employee')
            : 0.0;

        $nssfEmployer = $employee->nssf_enabled
            ? $this->calculateNssf($grossPay, $nssfRate, 'employer')
            : 0.0;

        $shif = $employee->sha_enabled
            ? $this->calculateShif($grossPay, $shifRate)
            : 0.0;

        $housingEmployee = $employee->housing_levy_enabled
            ? $this->percentage(
                $grossPay,
                (float) ($housingRate?->employee_rate ?? 1.5)
            )
            : 0.0;

        $housingEmployer = $employee->housing_levy_enabled
            ? $this->percentage(
                $grossPay,
                (float) ($housingRate?->employer_rate ?? 1.5)
            )
            : 0.0;

        $registeredPension = min(
            30000,
            max(
                0,
                (float) ($employee->registered_pension_contribution ?? 0)
            )
        );

        $postRetirementMedical = min(
            15000,
            max(
                0,
                (float) (
                    $employee->post_retirement_medical_contribution
                    ?? 0
                )
            )
        );

        $mortgageInterest = min(
            30000,
            max(
                0,
                (float) ($employee->mortgage_interest ?? 0)
            )
        );

        /*
         * NSSF and registered pension deductions share the statutory
         * pension deduction ceiling. Avoid claiming more than KES 30,000.
         */
        $pensionDeduction = min(
            30000,
            $nssfEmployee + $registeredPension
        );

        $allowableDeductions =
            $pensionDeduction
            + $shif
            + $housingEmployee
            + $postRetirementMedical
            + $mortgageInterest;

        $taxablePay = max(
            0,
            $grossPay - $allowableDeductions
        );

        $insuranceRelief = min(
            5000,
            max(
                0,
                (float) ($employee->insurance_relief ?? 0)
            )
        );

        $paye = $employee->tax_enabled
            ? $this->calculatePaye(
                $taxablePay,
                $payeRate,
                $insuranceRelief
            )
            : 0.0;

        return [
            'taxable_pay' => round($taxablePay, 2),
            'paye' => round($paye, 2),
            'nssf' => round($nssfEmployee, 2),
            'employer_nssf' => round($nssfEmployer, 2),
            'sha' => round($shif, 2),
            'housing_levy' => round($housingEmployee, 2),
            'employer_housing_levy' =>
                round($housingEmployer, 2),
            'allowable_deductions' =>
                round($allowableDeductions, 2),
            'insurance_relief' => round($insuranceRelief, 2),
        ];
    }

    private function calculatePaye(
        float $taxablePay,
        ?PayrollStatutoryRate $rate,
        float $insuranceRelief = 0
    ): float {
        $bands = $rate?->bands ?: [
            ['amount' => 24000, 'rate' => 10],
            ['amount' => 8333, 'rate' => 25],
            ['amount' => 467667, 'rate' => 30],
            ['amount' => 300000, 'rate' => 32.5],
            ['amount' => null, 'rate' => 35],
        ];

        $remaining = max(0, $taxablePay);
        $tax = 0.0;

        foreach ($bands as $band) {
            if ($remaining <= 0) {
                break;
            }

            $amount = $band['amount'] ?? null;
            $bandRate = (float) ($band['rate'] ?? 0);

            $taxableInBand = $amount === null
                ? $remaining
                : min($remaining, (float) $amount);

            $tax += $taxableInBand * ($bandRate / 100);
            $remaining -= $taxableInBand;
        }

        $personalRelief = (float) (
            $rate?->personal_relief ?? 2400
        );

        return max(
            0,
            $tax - $personalRelief - $insuranceRelief
        );
    }

    private function calculateNssf(
        float $grossPay,
        ?PayrollStatutoryRate $rate,
        string $side
    ): float {
        $percentage = (float) (
            $side === 'employer'
                ? ($rate?->employer_rate ?? 6)
                : ($rate?->employee_rate ?? 6)
        );

        $upperLimit = (float) (
            $rate?->upper_earning_limit ?? 108000
        );

        $contribution = $this->percentage(
            min($grossPay, $upperLimit),
            $percentage
        );

        if ($rate?->maximum_amount !== null) {
            $contribution = min(
                $contribution,
                (float) $rate->maximum_amount
            );
        }

        return max(0, $contribution);
    }

    private function calculateShif(
        float $grossPay,
        ?PayrollStatutoryRate $rate
    ): float {
        $contribution = $this->percentage(
            $grossPay,
            (float) ($rate?->employee_rate ?? 2.75)
        );

        return max(
            (float) ($rate?->minimum_amount ?? 300),
            $contribution
        );
    }

    private function percentage(
        float $amount,
        float $rate
    ): float {
        return round(
            max(0, $amount) * ($rate / 100),
            2
        );
    }
}
