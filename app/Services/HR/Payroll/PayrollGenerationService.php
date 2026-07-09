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
    ) {}

    public function generate(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll) {
            PayrollItem::where('payroll_id', $payroll->id)->delete();
            Payslip::where('payroll_id', $payroll->id)->delete();

            $employees = Employee::query()
                ->where('status', 'active')
                ->where('is_active', true)
                ->get();

            if ($employees->isEmpty()) {
                throw new \Exception('No active employees found for payroll generation.');
            }

            foreach ($employees as $employee) {
                $basicSalary = (float) ($employee->basic_salary ?? 0);

                $allowancesTotal =
                    (float) ($employee->house_allowance ?? 0)
                    + (float) ($employee->transport_allowance ?? 0)
                    + (float) ($employee->other_allowance ?? 0);

                $overtimeAmount = 0.0;
                $grossPay = $basicSalary + $allowancesTotal + $overtimeAmount;

                $statutory = $this->statutory->calculate($grossPay, $employee);

                $paye = (float) ($statutory['paye'] ?? 0);
                $nssf = (float) ($statutory['nssf'] ?? 0);
                $sha = (float) ($statutory['sha'] ?? 0);
                $housingLevy = (float) ($statutory['housing_levy'] ?? 0);

                $salaryAdvanceQuery = SalaryAdvance::query()
                    ->where('employee_id', $employee->id);

                if (Schema::hasColumn('salary_advances', 'approval_status')) {
                    $salaryAdvanceQuery->where('approval_status', 'approved');
                }

                if (Schema::hasColumn('salary_advances', 'balance')) {
                    $salaryAdvanceQuery->where('balance', '>', 0);
                }

                $salaryAdvanceRecovery = 0.0;
                $remainingApprovedAdvances = 0.0;

                $approvedAdvances = $salaryAdvanceQuery->get();

                foreach ($approvedAdvances as $advance) {
                    $deduction = (float) ($advance->monthly_deduction ?? 0);
                    $balance = (float) ($advance->balance ?? 0);

                    $actualRecovery = min($deduction, $balance);
                    $salaryAdvanceRecovery += $actualRecovery;

                    $remainingApprovedAdvances += max($balance - $actualRecovery, 0);
                }

                $otherDeductions = 0.0;
                $statutoryDeductions = $nssf + $sha + $housingLevy;  // PAYE excluded here
                $totalDeductions = $statutoryDeductions + $paye + $salaryAdvanceRecovery + $otherDeductions;

                $taxablePay = max($grossPay - $nssf - $sha - $housingLevy, 0);

                $netPay = $grossPay - $totalDeductions;

                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'allowances_total' => $allowancesTotal,
                    'overtime_amount' => $overtimeAmount,
                    'gross_pay' => $grossPay,
                    'taxable_pay' => $taxablePay,
                    'paye' => $paye,
                    'nssf' => $nssf,
                    'sha' => $sha,
                    'housing_levy' => $housingLevy,
                    'salary_advance_deduction' => $salaryAdvanceRecovery,
                    'other_deductions' => $otherDeductions,
                    'net_pay' => $netPay,
                    'days_worked' => 30,
                    'leave_days' => 0,
                    'absent_days' => 0,
                    'status' => 'generated',
                ]);

                Payslip::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'pay_period_start' => $payroll->period_start,
                    'pay_period_end' => $payroll->period_end,
                    'gross_pay' => $grossPay,
                    'taxable_pay' => $taxablePay,
                    'paye' => $paye,
                    'statutory_deductions' => $statutoryDeductions,
                    'other_deductions' => $salaryAdvanceRecovery,
                    'net_pay' => $netPay,
                    'email_sent' => false,
                ]);
            }

            $payroll->update([
                'status' => 'generated',
                'generated_by' => auth()->id(),
            ]);
        });
    }
}
