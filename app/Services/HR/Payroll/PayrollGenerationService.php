<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use App\Models\HR\PayrollItem;
use App\Models\HR\Payslip;
use App\Models\HR\SalaryAdvance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollGenerationService
{
    public function __construct(
        protected StatutoryDeductionService $statutory
    ) {
    }

    public function generate(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll): void {
            if (
                $payroll->payments()
                    ->where('status', 'posted')
                    ->exists()
            ) {
                throw new \RuntimeException(
                    'Payroll cannot be regenerated after salary payments '
                    . 'have been posted.'
                );
            }

            PayrollItem::query()
                ->where('payroll_id', $payroll->id)
                ->delete();

            Payslip::query()
                ->where('payroll_id', $payroll->id)
                ->delete();

            $employees = Employee::query()
                ->where('status', 'active')
                ->where('is_active', true)
                ->get();

            if ($employees->isEmpty()) {
                throw new \RuntimeException(
                    'No active employees found for payroll generation.'
                );
            }

            foreach ($employees as $employee) {
                $basicSalary = (float) (
                    $employee->basic_salary ?? 0
                );

                $allowancesTotal =
                    (float) ($employee->house_allowance ?? 0)
                    + (float) ($employee->transport_allowance ?? 0)
                    + (float) ($employee->other_allowance ?? 0);

                $overtimeAmount = 0.0;
                $grossPay =
                    $basicSalary
                    + $allowancesTotal
                    + $overtimeAmount;

                $statutory = $this->statutory->calculate(
                    $grossPay,
                    $employee,
                    $payroll->period_end
                );

                $paye = (float) ($statutory['paye'] ?? 0);
                $nssf = (float) ($statutory['nssf'] ?? 0);
                $employerNssf = (float) (
                    $statutory['employer_nssf'] ?? 0
                );
                $sha = (float) ($statutory['sha'] ?? 0);
                $housingLevy = (float) (
                    $statutory['housing_levy'] ?? 0
                );
                $employerHousingLevy = (float) (
                    $statutory['employer_housing_levy'] ?? 0
                );

                $salaryAdvanceQuery = SalaryAdvance::query()
                    ->where('employee_id', $employee->id);

                if (
                    Schema::hasColumn(
                        'salary_advances',
                        'approval_status'
                    )
                ) {
                    $salaryAdvanceQuery->where(
                        'approval_status',
                        'approved'
                    );
                }

                if (
                    Schema::hasColumn(
                        'salary_advances',
                        'balance'
                    )
                ) {
                    $salaryAdvanceQuery->where('balance', '>', 0);
                }

                $salaryAdvanceRecovery = 0.0;

                foreach ($salaryAdvanceQuery->get() as $advance) {
                    $deduction = (float) (
                        $advance->monthly_deduction ?? 0
                    );
                    $balance = (float) ($advance->balance ?? 0);

                    $salaryAdvanceRecovery += min(
                        $deduction,
                        $balance
                    );
                }

                $otherDeductions = 0.0;

                $totalEmployeeDeductions =
                    $paye
                    + $nssf
                    + $sha
                    + $housingLevy
                    + $salaryAdvanceRecovery
                    + $otherDeductions;

                $netPay = max(
                    0,
                    $grossPay - $totalEmployeeDeductions
                );

                PayrollItem::query()->create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'allowances_total' => $allowancesTotal,
                    'overtime_amount' => $overtimeAmount,
                    'gross_pay' => $grossPay,
                    'taxable_pay' =>
                        (float) ($statutory['taxable_pay'] ?? 0),
                    'paye' => $paye,
                    'nssf' => $nssf,
                    'employer_nssf' => $employerNssf,
                    'sha' => $sha,
                    'housing_levy' => $housingLevy,
                    'employer_housing_levy' =>
                        $employerHousingLevy,
                    'salary_advance_deduction' =>
                        $salaryAdvanceRecovery,
                    'other_deductions' => $otherDeductions,
                    'net_pay' => $netPay,
                    'paid_amount' => 0,
                    'payment_status' => 'unpaid',
                    'days_worked' => 30,
                    'leave_days' => 0,
                    'absent_days' => 0,
                    'status' => 'generated',
                ]);

                Payslip::query()->create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'pay_period_start' => $payroll->period_start,
                    'pay_period_end' => $payroll->period_end,
                    'gross_pay' => $grossPay,
                    'taxable_pay' =>
                        (float) ($statutory['taxable_pay'] ?? 0),
                    'paye' => $paye,
                    'statutory_deductions' =>
                        $nssf + $sha + $housingLevy,
                    'other_deductions' =>
                        $salaryAdvanceRecovery + $otherDeductions,
                    'net_pay' => $netPay,
                    'email_sent' => false,
                ]);
            }

            $this->refreshTotals($payroll);

            $payroll->forceFill([
                'status' => 'generated',
                'generated_by' => auth()->id(),
            ])->save();
        });
    }

    public function refreshTotals(Payroll $payroll): void
    {
        $totals = $payroll->items()
            ->selectRaw('
                COALESCE(SUM(gross_pay), 0) as total_gross,
                COALESCE(SUM(paye), 0) as total_paye,
                COALESCE(SUM(nssf), 0) as total_nssf_employee,
                COALESCE(SUM(employer_nssf), 0) as total_nssf_employer,
                COALESCE(SUM(sha), 0) as total_shif,
                COALESCE(SUM(housing_levy), 0) as total_housing_employee,
                COALESCE(SUM(employer_housing_levy), 0) as total_housing_employer,
                COALESCE(SUM(salary_advance_deduction), 0) as total_advances,
                COALESCE(SUM(other_deductions), 0) as total_other,
                COALESCE(SUM(net_pay), 0) as total_net
            ')
            ->first();

        $totalGross = (float) $totals->total_gross;
        $employerNssf = (float) $totals->total_nssf_employer;
        $employerHousing = (float) $totals->total_housing_employer;
        $totalNet = (float) $totals->total_net;

        $payroll->forceFill([
            'total_gross' => $totalGross,
            'total_paye' => (float) $totals->total_paye,
            'total_nssf_employee' =>
                (float) $totals->total_nssf_employee,
            'total_nssf_employer' => $employerNssf,
            'total_shif' => (float) $totals->total_shif,
            'total_housing_levy_employee' =>
                (float) $totals->total_housing_employee,
            'total_housing_levy_employer' =>
                $employerHousing,
            'total_salary_advance_deductions' =>
                (float) $totals->total_advances,
            'total_other_deductions' =>
                (float) $totals->total_other,
            'total_net' => $totalNet,
            'total_employer_cost' =>
                $totalGross + $employerNssf + $employerHousing,
            'total_paid' => 0,
            'balance_due' => $totalNet,
            'payment_status' => 'unpaid',
        ])->saveQuietly();
    }
}
