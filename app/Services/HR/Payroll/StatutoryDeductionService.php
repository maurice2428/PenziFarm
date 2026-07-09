<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\Employee;

class StatutoryDeductionService
{
    public function calculate(float $grossPay, Employee $employee): array
    {
        $paye = $employee->tax_enabled ? $this->calculatePaye($grossPay) : 0.00;
        $nssf = $employee->nssf_enabled ? $this->calculateNssf($grossPay) : 0.00;
        $sha = $employee->sha_enabled ? $this->calculateSha($grossPay) : 0.00;
        $housingLevy = $employee->housing_levy_enabled ? $this->calculateHousingLevy($grossPay) : 0.00;

        return [
            'paye' => round($paye, 2),
            'nssf' => round($nssf, 2),
            'sha' => round($sha, 2),
            'housing_levy' => round($housingLevy, 2),
        ];
    }

    protected function calculatePaye(float $grossPay): float
    {
        if ($grossPay <= 24000) {
            return $grossPay * 0.10;
        }

        if ($grossPay <= 32333) {
            return 2400 + (($grossPay - 24000) * 0.25);
        }

        return 4483.25 + (($grossPay - 32333) * 0.30);
    }

    protected function calculateNssf(float $grossPay): float
    {
        return min($grossPay * 0.06, 2160);
    }

    protected function calculateSha(float $grossPay): float
    {
        return $grossPay * 0.0275;
    }

    protected function calculateHousingLevy(float $grossPay): float
    {
        return $grossPay * 0.015;
    }
}
