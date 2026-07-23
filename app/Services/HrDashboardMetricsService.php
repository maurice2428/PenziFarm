<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HrDashboardMetricsService
{
    private const TIMEZONE = 'Africa/Nairobi';

    public function snapshot(): array
    {
        $now = now(self::TIMEZONE);
        $payrollTable = $this->firstExistingTable([
            'payrolls',
            'payroll_runs',
            'employee_payrolls',
        ]);

        $payroll = $payrollTable
            ? $this->resolvePayroll($payrollTable, $now)
            : null;

        $payrollItemsTable = $this->firstExistingTable([
            'payroll_items',
            'employee_payroll_items',
            'payroll_entries',
            'payslip_items',
        ]);

        $itemsQuery = $this->payrollItemsQuery(
            $payrollItemsTable,
            $payroll?->id ?? null
        );

        $genericItems = $this->genericPayrollItemTotals(
            $payrollItemsTable,
            $itemsQuery
        );

        $basicSalary = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['basic_salary', 'basic_pay', 'base_salary', 'salary'],
            ['basic_salary', 'basic_pay', 'base_salary', 'salary_total'],
            $genericItems['basic_salary']
        );

        $allowances = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['total_allowances', 'allowances', 'allowance_amount', 'allowance_total'],
            ['total_allowances', 'allowances', 'allowance_amount', 'allowance_total'],
            $genericItems['allowances']
        );

        $grossPay = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['gross_salary', 'gross_pay', 'gross_amount', 'total_gross', 'gross_total'],
            ['gross_salary', 'gross_pay', 'gross_amount', 'total_gross', 'gross_total'],
            $genericItems['gross_pay']
        );

        if ($grossPay <= 0 && ($basicSalary > 0 || $allowances > 0)) {
            $grossPay = $basicSalary + $allowances;
        }

        $paye = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['paye', 'paye_amount', 'paye_tax', 'paye_deduction'],
            ['paye', 'paye_amount', 'paye_tax', 'paye_deduction', 'total_paye'],
            $genericItems['paye']
        );

        $nssf = $this->statutoryMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['total_nssf', 'nssf', 'nssf_amount', 'nssf_deduction'],
            ['employee_nssf', 'nssf_employee'],
            ['employer_nssf', 'nssf_employer'],
            ['total_nssf', 'nssf', 'nssf_amount', 'nssf_deduction'],
            $genericItems['nssf']
        );

        $sha = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['sha', 'sha_amount', 'sha_deduction', 'shif', 'shif_amount', 'nhif', 'nhif_amount'],
            ['sha', 'sha_amount', 'sha_deduction', 'shif', 'shif_amount', 'nhif', 'nhif_amount'],
            $genericItems['sha']
        );

        $housingLevy = $this->statutoryMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['total_housing_levy', 'housing_levy', 'housing_levy_amount', 'housing_levy_deduction'],
            ['employee_housing_levy', 'housing_levy_employee'],
            ['employer_housing_levy', 'housing_levy_employer'],
            ['total_housing_levy', 'housing_levy', 'housing_levy_amount', 'housing_levy_deduction'],
            $genericItems['housing_levy']
        );

        /*
        |--------------------------------------------------------------------------
        | NITA Levy
        |--------------------------------------------------------------------------
        |
        | NITA is not read from payroll_items. It is calculated after resolving
        | the workforce snapshot: KES 50 multiplied by active employees only.
        */
        $nitaRatePerActiveEmployee = 50.00;
        $nitaActiveEmployeeCount = 0;
        $nita = 0.00;

        $salaryAdvances = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['salary_advance', 'salary_advance_deduction', 'advance_deduction'],
            ['salary_advance', 'salary_advance_deduction', 'advance_deduction'],
            $genericItems['salary_advance']
        );

        $otherDeductions = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['other_deductions', 'other_deduction', 'misc_deductions'],
            ['other_deductions', 'other_deduction', 'misc_deductions'],
            $genericItems['other_deductions']
        );

        $totalDeductions = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['total_deductions', 'deductions_total'],
            ['total_deductions', 'deductions_total'],
            $genericItems['total_deductions']
        );

        if ($totalDeductions <= 0) {
            $totalDeductions = $paye
                + $nssf
                + $sha
                + $housingLevy
                + $salaryAdvances
                + $otherDeductions;
        }

        $netPay = $this->payrollMetric(
            $payroll,
            $payrollItemsTable,
            $itemsQuery,
            ['net_salary', 'net_pay', 'salary_payable', 'net_amount', 'total_net'],
            ['net_salary', 'net_pay', 'salary_payable', 'net_amount', 'total_net'],
            $genericItems['net_pay']
        );

        if ($netPay <= 0 && $grossPay > 0) {
            $netPay = max(0, $grossPay - $totalDeductions);
        }

        $employeeCount = $this->payrollEmployeeCount(
            $payroll,
            $payrollItemsTable,
            $itemsQuery
        );

        $workforce = $this->workforceSnapshot($now);

        $nitaActiveEmployeeCount = (int) ($workforce['active_staff'] ?? 0);
        $nita = $nitaActiveEmployeeCount * $nitaRatePerActiveEmployee;

        return [
            'generated_at' => $now,
            'payroll' => [
                'exists' => (bool) $payroll,
                'id' => $payroll?->id,
                'period_label' => $this->payrollPeriodLabel($payroll, $now),
                'is_current_period' => $this->isCurrentPayrollPeriod($payroll, $now),
                'status' => $this->rowString($payroll, ['status', 'payroll_status', 'state']) ?: 'Not generated',
                'employee_count' => $employeeCount,
                'source' => $payrollItemsTable && $itemsQuery
                    ? $payrollItemsTable
                    : ($payrollTable ?: 'No payroll table'),
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'gross_pay' => $grossPay,
                'net_pay' => $netPay,
                'total_deductions' => $totalDeductions,
                'paye' => $paye,
                'nssf' => $nssf,
                'sha' => $sha,
                'housing_levy' => $housingLevy,
                'nita' => $nita,
                'nita_rate_per_active_employee' => $nitaRatePerActiveEmployee,
                'nita_active_employee_count' => $nitaActiveEmployeeCount,
                'salary_advances' => $salaryAdvances,
                'other_deductions' => $otherDeductions,
            ],
            'workforce' => $workforce,
            'pending_leaves' => $this->pendingLeaveApplications(),
            'pending_advances' => $this->pendingSalaryAdvances(),
            'external_portals' => [
                'paye' => [
                    'label' => 'KRA iTax',
                    'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                ],
                'nssf' => [
                    'label' => 'NSSF',
                    'url' => 'https://eservice.nssfkenya.co.ke/',
                ],
                'sha' => [
                    'label' => 'SHA',
                    'url' => 'https://employers.sha.go.ke/members',
                ],
                'housing_levy' => [
                    'label' => 'Boma Yangu',
                    'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                ],
                'nita' => [
                    'label' => 'NITA',
                    'url' => 'https://www.nita.go.ke/',
                ],
            ],
        ];
    }

    private function resolvePayroll(string $table, Carbon $now): ?object
    {
        $base = $this->withoutSoftDeleted(DB::table($table), $table);
        $current = clone $base;

        if ($this->hasColumn($table, 'month') && $this->hasColumn($table, 'year')) {
            $current
                ->where('month', $now->month)
                ->where('year', $now->year);
        } elseif ($this->hasColumn($table, 'payroll_month')) {
            $current->where(function (Builder $query) use ($now): void {
                $query
                    ->where('payroll_month', $now->format('Y-m'))
                    ->orWhere('payroll_month', $now->format('Y-m-01'))
                    ->orWhere('payroll_month', $now->format('F Y'));
            });
        } else {
            $dateColumn = $this->firstExistingColumn($table, [
                'payroll_date',
                'period_start',
                'processed_at',
                'approved_at',
                'created_at',
            ]);

            if ($dateColumn) {
                $current->whereBetween($dateColumn, [
                    $now->copy()->startOfMonth()->toDateTimeString(),
                    $now->copy()->endOfMonth()->toDateTimeString(),
                ]);
            }
        }

        $currentPayroll = $this->latestPayroll($current, $table)->first();

        if ($currentPayroll) {
            return $currentPayroll;
        }

        return $this->latestPayroll($base, $table)->first();
    }

    private function latestPayroll(Builder $query, string $table): Builder
    {
        if ($this->hasColumn($table, 'year')) {
            $query->orderByDesc('year');
        }

        if ($this->hasColumn($table, 'month')) {
            $query->orderByDesc('month');
        }

        foreach (['processed_at', 'approved_at', 'payroll_date', 'created_at', 'id'] as $column) {
            if ($this->hasColumn($table, $column)) {
                $query->orderByDesc($column);
                break;
            }
        }

        return $query;
    }

    private function payrollItemsQuery(?string $table, mixed $payrollId): ?Builder
    {
        if (! $table || ! $payrollId) {
            return null;
        }

        $foreignKey = $this->firstExistingColumn($table, [
            'payroll_id',
            'payroll_run_id',
            'employee_payroll_id',
        ]);

        if (! $foreignKey) {
            return null;
        }

        return $this->withoutSoftDeleted(
            DB::table($table)->where($foreignKey, $payrollId),
            $table
        );
    }

    private function payrollMetric(
        ?object $payroll,
        ?string $itemsTable,
        ?Builder $itemsQuery,
        array $itemColumns,
        array $payrollColumns,
        float $genericValue
    ): float {
        $itemResult = $this->sumFirstExistingColumn(
            $itemsTable,
            $itemsQuery,
            $itemColumns
        );

        if ($itemResult['found']) {
            return $itemResult['value'];
        }

        if ($genericValue > 0) {
            return $genericValue;
        }

        return $this->rowFloat($payroll, $payrollColumns);
    }

    private function statutoryMetric(
        ?object $payroll,
        ?string $itemsTable,
        ?Builder $itemsQuery,
        array $canonicalColumns,
        array $employeeColumns,
        array $employerColumns,
        array $payrollColumns,
        float $genericValue
    ): float {
        $canonical = $this->sumFirstExistingColumn(
            $itemsTable,
            $itemsQuery,
            $canonicalColumns
        );

        if ($canonical['found']) {
            return $canonical['value'];
        }

        $employee = $this->sumFirstExistingColumn(
            $itemsTable,
            $itemsQuery,
            $employeeColumns
        );

        $employer = $this->sumFirstExistingColumn(
            $itemsTable,
            $itemsQuery,
            $employerColumns
        );

        if ($employee['found'] || $employer['found']) {
            return $employee['value'] + $employer['value'];
        }

        if ($genericValue > 0) {
            return $genericValue;
        }

        return $this->rowFloat($payroll, $payrollColumns);
    }

    private function genericPayrollItemTotals(
        ?string $table,
        ?Builder $query
    ): array {
        $totals = [
            'basic_salary' => 0.0,
            'allowances' => 0.0,
            'gross_pay' => 0.0,
            'net_pay' => 0.0,
            'total_deductions' => 0.0,
            'paye' => 0.0,
            'nssf' => 0.0,
            'sha' => 0.0,
            'housing_levy' => 0.0,
            'nita' => 0.0,
            'salary_advance' => 0.0,
            'other_deductions' => 0.0,
        ];

        if (! $table || ! $query) {
            return $totals;
        }

        $amountColumn = $this->firstExistingColumn($table, [
            'amount',
            'item_amount',
            'value',
            'total',
            'calculated_amount',
        ]);

        $labelColumns = array_values(array_filter([
            $this->firstExistingColumn($table, ['name', 'item_name', 'label']),
            $this->firstExistingColumn($table, ['code', 'item_code', 'component_code']),
            $this->firstExistingColumn($table, ['type', 'item_type', 'category', 'component_type']),
            $this->firstExistingColumn($table, ['description', 'notes']),
        ]));

        if (! $amountColumn || $labelColumns === []) {
            return $totals;
        }

        try {
            $rows = (clone $query)
                ->get(array_values(array_unique(array_merge(
                    [$amountColumn],
                    $labelColumns
                ))));
        } catch (Throwable) {
            return $totals;
        }

        foreach ($rows as $row) {
            $label = strtolower(trim(implode(' ', array_map(
                fn (string $column): string => (string) ($row->{$column} ?? ''),
                $labelColumns
            ))));

            $amount = (float) ($row->{$amountColumn} ?? 0);

            if ($amount == 0.0 || $label === '') {
                continue;
            }

            $metric = $this->classifyPayrollItem($label);

            if ($metric) {
                $totals[$metric] += $amount;
            }
        }

        return $totals;
    }

    private function classifyPayrollItem(string $label): ?string
    {
        $contains = fn (array $needles): bool => collect($needles)
            ->contains(fn (string $needle): bool => str_contains($label, $needle));

        return match (true) {
            $contains(['paye', 'income tax']) => 'paye',
            $contains(['nssf']) => 'nssf',
            $contains(['sha', 'shif', 'nhif']) => 'sha',
            $contains(['housing levy', 'ahl']) => 'housing_levy',
            $contains(['nita']) => 'nita',
            $contains(['salary advance', 'advance deduction']) => 'salary_advance',
            $contains(['total deduction', 'deductions total']) => 'total_deductions',
            $contains(['other deduction', 'misc deduction']) => 'other_deductions',
            $contains(['net salary', 'net pay', 'salary payable']) => 'net_pay',
            $contains(['gross salary', 'gross pay', 'gross payroll']) => 'gross_pay',
            $contains(['basic salary', 'basic pay', 'base salary']) => 'basic_salary',
            $contains(['allowance', 'benefit']) => 'allowances',
            default => null,
        };
    }

    private function payrollEmployeeCount(
        ?object $payroll,
        ?string $itemsTable,
        ?Builder $itemsQuery
    ): int {
        if ($itemsTable && $itemsQuery) {
            $employeeColumn = $this->firstExistingColumn($itemsTable, [
                'employee_id',
                'staff_id',
                'user_id',
            ]);

            if ($employeeColumn) {
                try {
                    return (int) (clone $itemsQuery)
                        ->whereNotNull($employeeColumn)
                        ->distinct()
                        ->count($employeeColumn);
                } catch (Throwable) {
                    // Use payroll summary below.
                }
            }
        }

        return (int) $this->rowFloat($payroll, [
            'employee_count',
            'staff_count',
            'employees_processed',
            'total_employees',
        ]);
    }

    private function payrollPeriodLabel(?object $payroll, Carbon $now): string
    {
        if (! $payroll) {
            return $now->format('F Y');
        }

        $year = (int) ($payroll->year ?? 0);
        $month = (int) ($payroll->month ?? 0);

        if ($year > 0 && $month >= 1 && $month <= 12) {
            return Carbon::create($year, $month, 1, 0, 0, 0, self::TIMEZONE)
                ->format('F Y');
        }

        foreach (['payroll_month', 'period_start', 'payroll_date', 'processed_at', 'created_at'] as $column) {
            if (! empty($payroll->{$column})) {
                try {
                    return Carbon::parse($payroll->{$column}, self::TIMEZONE)
                        ->format('F Y');
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $now->format('F Y');
    }

    private function isCurrentPayrollPeriod(?object $payroll, Carbon $now): bool
    {
        if (! $payroll) {
            return false;
        }

        return $this->payrollPeriodLabel($payroll, $now) === $now->format('F Y');
    }

    private function workforceSnapshot(Carbon $now): array
    {
        $employeesTable = $this->firstExistingTable(['employees']);
        $attendanceTable = $this->firstExistingTable([
            'attendance_records',
            'attendances',
            'employee_attendances',
            'hr_attendances',
        ]);
        $leaveTable = $this->firstExistingTable([
            'leave_applications',
            'leave_requests',
            'employee_leaves',
            'leaves',
        ]);
        $advanceTable = $this->firstExistingTable([
            'salary_advances',
            'employee_salary_advances',
            'payroll_salary_advances',
        ]);

        $total = 0;
        $active = 0;
        $exited = 0;

        if ($employeesTable) {
            $total = (int) $this->withoutSoftDeleted(
                DB::table($employeesTable),
                $employeesTable
            )->count();

            $statusColumn = $this->firstExistingColumn($employeesTable, [
                'employment_status',
                'status',
            ]);

            if ($statusColumn) {
                $active = (int) $this->withoutSoftDeleted(DB::table($employeesTable), $employeesTable)
                    ->whereIn(DB::raw("LOWER({$statusColumn})"), [
                        'active',
                        'currently active',
                        'employed',
                    ])
                    ->count();

                $exited = (int) $this->withoutSoftDeleted(DB::table($employeesTable), $employeesTable)
                    ->whereIn(DB::raw("LOWER({$statusColumn})"), [
                        'exited',
                        'inactive',
                        'terminated',
                        'resigned',
                        'dismissed',
                        'suspended',
                    ])
                    ->count();
            } elseif ($this->hasColumn($employeesTable, 'is_active')) {
                $active = (int) $this->withoutSoftDeleted(DB::table($employeesTable), $employeesTable)
                    ->where('is_active', true)
                    ->count();
                $exited = max(0, $total - $active);
            } else {
                $active = $total;
            }
        }

        $present = 0;
        $absent = 0;

        if ($attendanceTable) {
            $dateColumn = $this->firstExistingColumn($attendanceTable, [
                'attendance_date',
                'recorded_on',
                'date',
            ]);
            $statusColumn = $this->firstExistingColumn($attendanceTable, ['status']);

            if ($dateColumn && $statusColumn) {
                $present = (int) $this->withoutSoftDeleted(DB::table($attendanceTable), $attendanceTable)
                    ->whereDate($dateColumn, $now->toDateString())
                    ->whereIn(DB::raw("LOWER({$statusColumn})"), [
                        'present',
                        'clocked_in',
                        'checked_in',
                        'late',
                    ])
                    ->count();

                $absent = (int) $this->withoutSoftDeleted(DB::table($attendanceTable), $attendanceTable)
                    ->whereDate($dateColumn, $now->toDateString())
                    ->whereIn(DB::raw("LOWER({$statusColumn})"), [
                        'absent',
                        'missed',
                        'no_show',
                    ])
                    ->count();
            }
        }

        $pendingLeave = 0;
        $onLeave = 0;

        if ($leaveTable) {
            $statusColumn = $this->firstExistingColumn($leaveTable, ['status']);
            $pendingQuery = $this->withoutSoftDeleted(DB::table($leaveTable), $leaveTable);
            $onLeaveQuery = $this->withoutSoftDeleted(DB::table($leaveTable), $leaveTable);

            if ($statusColumn) {
                $pendingQuery->whereIn(DB::raw("LOWER({$statusColumn})"), [
                    'pending',
                    'submitted',
                    'awaiting approval',
                ]);
                $onLeaveQuery->whereIn(DB::raw("LOWER({$statusColumn})"), [
                    'approved',
                    'active',
                ]);
            }

            $pendingLeave = (int) $pendingQuery->count();

            $startColumn = $this->firstExistingColumn($leaveTable, ['start_date', 'from_date']);
            $endColumn = $this->firstExistingColumn($leaveTable, ['end_date', 'to_date']);

            if ($startColumn && $endColumn) {
                $onLeave = (int) $onLeaveQuery
                    ->whereDate($startColumn, '<=', $now->toDateString())
                    ->whereDate($endColumn, '>=', $now->toDateString())
                    ->count();
            }
        }

        $pendingAdvances = 0;

        if ($advanceTable) {
            $query = $this->withoutSoftDeleted(DB::table($advanceTable), $advanceTable);
            $statusColumn = $this->firstExistingColumn($advanceTable, ['status']);

            if ($statusColumn) {
                $query->whereIn(DB::raw("LOWER({$statusColumn})"), [
                    'pending',
                    'submitted',
                    'awaiting approval',
                ]);
            }

            $pendingAdvances = (int) $query->count();
        }

        return [
            'present_today' => $present,
            'absent_today' => $absent,
            'on_leave_today' => $onLeave,
            'pending_leave' => $pendingLeave,
            'pending_advances' => $pendingAdvances,
            'active_staff' => $active,
            'exited_staff' => $exited,
            'total_employees' => $total,
        ];
    }

    private function pendingLeaveApplications(): Collection
    {
        $model = '\\App\\Models\\LeaveApplication';

        if (! class_exists($model)) {
            return collect();
        }

        try {
            return $model::query()
                ->with(['employee', 'leaveType'])
                ->whereIn('status', ['pending', 'submitted', 'awaiting approval'])
                ->latest()
                ->limit(6)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function pendingSalaryAdvances(): Collection
    {
        $model = '\\App\\Models\\SalaryAdvance';

        if (! class_exists($model)) {
            return collect();
        }

        try {
            return $model::query()
                ->with(['employee'])
                ->whereIn('status', ['pending', 'submitted', 'awaiting approval'])
                ->latest()
                ->limit(6)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function sumFirstExistingColumn(
        ?string $table,
        ?Builder $query,
        array $columns
    ): array {
        if (! $table || ! $query) {
            return ['found' => false, 'value' => 0.0];
        }

        foreach ($columns as $column) {
            if (! $this->hasColumn($table, $column)) {
                continue;
            }

            try {
                return [
                    'found' => true,
                    'value' => (float) (clone $query)->sum($column),
                ];
            } catch (Throwable) {
                return ['found' => true, 'value' => 0.0];
            }
        }

        return ['found' => false, 'value' => 0.0];
    }

    private function rowFloat(?object $row, array $columns): float
    {
        if (! $row) {
            return 0.0;
        }

        foreach ($columns as $column) {
            if (isset($row->{$column}) && is_numeric($row->{$column})) {
                return (float) $row->{$column};
            }
        }

        return 0.0;
    }

    private function rowString(?object $row, array $columns): ?string
    {
        if (! $row) {
            return null;
        }

        foreach ($columns as $column) {
            if (isset($row->{$column}) && trim((string) $row->{$column}) !== '') {
                return trim((string) $row->{$column});
            }
        }

        return null;
    }

    private function withoutSoftDeleted(Builder $query, string $table): Builder
    {
        if ($this->hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        return $query;
    }

    private function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return $this->tableExists($table)
                && Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }
}
