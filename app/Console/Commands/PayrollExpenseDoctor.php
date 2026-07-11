<?php

namespace App\Console\Commands;

use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Finance\OperatingExpense;
use App\Models\HR\Payroll;
use App\Models\HR\PayrollStatutoryRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollExpenseDoctor extends Command
{
    protected $signature = 'payroll-expenses:doctor';
    protected $description = 'Check payroll payments, statutory rates, operating expenses and accounting mappings.';

    public function handle(): int
    {
        $requiredTables = [
            'payroll_statutory_rates',
            'payroll_payments',
            'payroll_payment_items',
            'statutory_remittances',
            'expense_categories',
            'operating_expenses',
            'operating_expense_payments',
        ];

        $tableRows = collect($requiredTables)->map(fn (string $table): array => [
            $table,
            Schema::hasTable($table) ? 'YES' : 'NO',
            Schema::hasTable($table) ? DB::table($table)->count() : '-',
        ])->all();

        $this->newLine();
        $this->info('Tables');
        $this->table(['Table', 'Exists', 'Rows'], $tableRows);

        $requiredMappings = [
            'salary_expense', 'salary_payable', 'paye_payable', 'nssf_payable',
            'shif_payable', 'housing_levy_payable', 'employer_nssf_expense',
            'employer_housing_levy_expense', 'salary_advance_receivable',
            'payroll_other_deductions_payable', 'cash_account', 'bank_account',
            'mpesa_account', 'accounts_payable', 'vat_input', 'withholding_tax_payable',
        ];

        $missingMappings = collect($requiredMappings)->filter(
            fn (string $key): bool => ! AccountingAccountMapping::query()
                ->where('key', $key)->where('is_active', true)->whereNotNull('account_id')->exists()
        )->values();

        $this->newLine();
        $this->info('Accounting Mappings');
        $this->line($missingMappings->isEmpty()
            ? '<fg=green>All required mappings are active.</>'
            : '<fg=red>Missing: ' . $missingMappings->implode(', ') . '</>');

        if (Schema::hasTable('payrolls')) {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Payrolls with salary balance', Payroll::query()->where('balance_due', '>', 0)->count()],
                    ['Total salary balance', 'KES ' . number_format((float) Payroll::query()->sum('balance_due'), 2)],
                    ['Active statutory rate rows', PayrollStatutoryRate::query()->where('is_active', true)->count()],
                ]
            );
        }

        if (Schema::hasTable('operating_expenses')) {
            $this->table(
                ['Expense Metric', 'Value'],
                [
                    ['Draft expenses', OperatingExpense::query()->where('status', 'draft')->count()],
                    ['Approved unpaid/partial', OperatingExpense::query()->whereIn('status', ['approved', 'partially_paid'])->count()],
                    ['Outstanding expense payables', 'KES ' . number_format((float) OperatingExpense::query()->sum('balance_due'), 2)],
                ]
            );
        }

        $healthy = collect($tableRows)->every(fn (array $row): bool => $row[1] === 'YES')
            && $missingMappings->isEmpty();

        $this->newLine();
        $healthy
            ? $this->info('Overall status: HEALTHY')
            : $this->error('Overall status: ACTION REQUIRED');

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
