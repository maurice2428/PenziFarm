<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Creates reversible DEMO-prefixed Projects & Works and Accounting records.
 *
 * Safety design:
 * - Every reference, project number, fund code, reconciliation number and
 *   account code is marked DEMO-.
 * - The seeder is idempotent: running it again updates the same demo records.
 * - It does not truncate or delete normal production records.
 * - A companion cleanup seeder removes only these DEMO-prefixed records.
 */
class LelekweProjectsAccountingDemoSeeder extends Seeder
{
    private ?int $userId = null;

    public function run(): void
    {
        $this->ensureRequiredTables();

        $this->userId = $this->resolveUserId();

        DB::transaction(function (): void {
            $accounts = $this->seedDemoChartOfAccounts();
            $costCenters = $this->seedCostCenters();
            [$fiscalYearId, $periodId] = $this->seedFiscalYearAndPeriods();
            $fundingSourceId = $this->seedFundingSource($accounts['director_capital']);
            $projectFunds = $this->seedProjectFunds($fundingSourceId, $costCenters['projects']);

            $this->seedAccountMappings($accounts);
            $this->seedTaxSettings();
            $journalEntries = $this->seedJournalEntries(
                $accounts,
                $costCenters,
                $fiscalYearId,
                $periodId,
                $projectFunds
            );
            $this->seedProjectFundTransactions($projectFunds, $fundingSourceId, $journalEntries);
            $this->syncProjectFundBalances($projectFunds);
            $this->seedDemoReconciliation($accounts['bank'], $journalEntries);
        });

        $this->seedProjectsAndWorks();

        $this->command?->info('Lelekwe demo data created/updated successfully.');
        $this->command?->warn('All demo records are marked DEMO-. Remove them before operational go-live using RemoveLelekweProjectsAccountingDemoSeeder.');
    }

    private function ensureRequiredTables(): void
    {
        $required = [
            'accounting_accounts',
            'accounting_fiscal_years',
            'accounting_periods',
            'accounting_cost_centers',
            'accounting_journal_entries',
            'accounting_journal_entry_lines',
            'accounting_funding_sources',
            'accounting_project_funds',
            'accounting_project_fund_transactions',
            'accounting_account_mappings',
            'accounting_tax_settings',
            'accounting_reconciliations',
        ];

        $missing = array_values(array_filter(
            $required,
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($missing !== []) {
            throw new \RuntimeException(
                'Required accounting tables are missing: ' . implode(', ', $missing) .
                '. Run php artisan migrate --force first.'
            );
        }
    }

    private function resolveUserId(): ?int
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')
            ->orderBy('id')
            ->value('id');
    }

    /** @return array<string, int> */
    private function seedDemoChartOfAccounts(): array
    {
        $assets = $this->upsert('accounting_accounts', ['code' => 'DEMO-1000'], [
            'code' => 'DEMO-1000',
            'name' => 'DEMO Assets',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => null,
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 9000,
            'description' => 'Demo ledger root. Remove before operational go-live.',
        ]);

        $liabilities = $this->upsert('accounting_accounts', ['code' => 'DEMO-2000'], [
            'code' => 'DEMO-2000',
            'name' => 'DEMO Liabilities',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'parent_id' => null,
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 9010,
            'description' => 'Demo ledger root. Remove before operational go-live.',
        ]);

        $equity = $this->upsert('accounting_accounts', ['code' => 'DEMO-3000'], [
            'code' => 'DEMO-3000',
            'name' => 'DEMO Equity',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'parent_id' => null,
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 9020,
            'description' => 'Demo ledger root. Remove before operational go-live.',
        ]);

        $income = $this->upsert('accounting_accounts', ['code' => 'DEMO-4000'], [
            'code' => 'DEMO-4000',
            'name' => 'DEMO Income',
            'type' => 'income',
            'normal_balance' => 'credit',
            'parent_id' => null,
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 9030,
            'description' => 'Demo ledger root. Remove before operational go-live.',
        ]);

        $expenses = $this->upsert('accounting_accounts', ['code' => 'DEMO-5000'], [
            'code' => 'DEMO-5000',
            'name' => 'DEMO Operating Expenses',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => null,
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 9040,
            'description' => 'Demo ledger root. Remove before operational go-live.',
        ]);

        return [
            'assets' => $assets,
            'bank' => $this->upsert('accounting_accounts', ['code' => 'DEMO-1010'], [
                'code' => 'DEMO-1010',
                'name' => 'DEMO Main Bank Account',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $assets,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9001,
                'description' => 'Sample bank account for demo reports.',
            ]),
            'petty_cash' => $this->upsert('accounting_accounts', ['code' => 'DEMO-1020'], [
                'code' => 'DEMO-1020',
                'name' => 'DEMO Petty Cash',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $assets,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9002,
                'description' => 'Sample petty-cash account for demo reports.',
            ]),
            'accounts_payable' => $this->upsert('accounting_accounts', ['code' => 'DEMO-2100'], [
                'code' => 'DEMO-2100',
                'name' => 'DEMO Accounts Payable',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $liabilities,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9011,
                'description' => 'Sample payable account for demo reports.',
            ]),
            'director_capital' => $this->upsert('accounting_accounts', ['code' => 'DEMO-3100'], [
                'code' => 'DEMO-3100',
                'name' => 'DEMO Director Capital',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'parent_id' => $equity,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9021,
                'description' => 'Sample director capital account for demo reports.',
            ]),
            'farm_sales' => $this->upsert('accounting_accounts', ['code' => 'DEMO-4100'], [
                'code' => 'DEMO-4100',
                'name' => 'DEMO Farm Sales Income',
                'type' => 'income',
                'normal_balance' => 'credit',
                'parent_id' => $income,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9031,
                'description' => 'Sample sales income for demo reports.',
            ]),
            'project_expense' => $this->upsert('accounting_accounts', ['code' => 'DEMO-5100'], [
                'code' => 'DEMO-5100',
                'name' => 'DEMO Project Materials Expense',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $expenses,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9041,
                'description' => 'Sample project materials expense for demo reports.',
            ]),
            'labour_expense' => $this->upsert('accounting_accounts', ['code' => 'DEMO-5200'], [
                'code' => 'DEMO-5200',
                'name' => 'DEMO Casual Labour Expense',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $expenses,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9042,
                'description' => 'Sample casual labour expense for demo reports.',
            ]),
            'fuel_expense' => $this->upsert('accounting_accounts', ['code' => 'DEMO-5300'], [
                'code' => 'DEMO-5300',
                'name' => 'DEMO Fuel and Transport Expense',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'parent_id' => $expenses,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 9043,
                'description' => 'Sample fuel expense for demo reports.',
            ]),
        ];
    }

    /** @return array<string, int> */
    private function seedCostCenters(): array
    {
        return [
            'admin' => $this->upsert('accounting_cost_centers', ['code' => 'DEMO-ADMIN'], [
                'code' => 'DEMO-ADMIN',
                'name' => 'DEMO Administration',
                'type' => 'admin',
                'is_active' => true,
                'description' => 'Sample administration cost centre.',
            ]),
            'projects' => $this->upsert('accounting_cost_centers', ['code' => 'DEMO-PROJECTS'], [
                'code' => 'DEMO-PROJECTS',
                'name' => 'DEMO Projects & Infrastructure',
                'type' => 'project',
                'is_active' => true,
                'description' => 'Sample Projects & Works cost centre.',
            ]),
            'crops' => $this->upsert('accounting_cost_centers', ['code' => 'DEMO-CROPS'], [
                'code' => 'DEMO-CROPS',
                'name' => 'DEMO Crop Farming',
                'type' => 'crop',
                'is_active' => true,
                'description' => 'Sample crop farming cost centre.',
            ]),
        ];
    }

    /** @return array{0:int,1:int} */
    private function seedFiscalYearAndPeriods(): array
    {
        $year = now()->year;
        $fiscalYearId = $this->upsert('accounting_fiscal_years', ['name' => "DEMO FY {$year}"], [
            'name' => "DEMO FY {$year}",
            'start_date' => Carbon::create($year, 1, 1)->toDateString(),
            'end_date' => Carbon::create($year, 12, 31)->toDateString(),
            'status' => 'open',
            'is_current' => false,
            'notes' => 'Demo fiscal year. Remove before operational go-live.',
            'created_by' => $this->userId,
        ]);

        $currentPeriodId = null;

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1);
            $periodId = $this->upsert('accounting_periods', [
                'fiscal_year_id' => $fiscalYearId,
                'period_number' => $month,
            ], [
                'fiscal_year_id' => $fiscalYearId,
                'period_number' => $month,
                'name' => 'DEMO ' . $start->format('F Y'),
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->endOfMonth()->toDateString(),
                'status' => 'open',
            ]);

            if ($month === now()->month) {
                $currentPeriodId = $periodId;
            }
        }

        return [$fiscalYearId, $currentPeriodId ?? $this->upsert('accounting_periods', [
            'fiscal_year_id' => $fiscalYearId,
            'period_number' => 1,
        ], [
            'fiscal_year_id' => $fiscalYearId,
            'period_number' => 1,
            'name' => "DEMO January {$year}",
            'start_date' => Carbon::create($year, 1, 1)->toDateString(),
            'end_date' => Carbon::create($year, 1, 1)->endOfMonth()->toDateString(),
            'status' => 'open',
        ])];
    }

    private function seedFundingSource(int $directorCapitalAccountId): int
    {
        return $this->upsert('accounting_funding_sources', [
            'name' => 'DEMO — Director Capital Contribution',
        ], [
            'name' => 'DEMO — Director Capital Contribution',
            'type' => 'director_capital',
            'linked_account_id' => $directorCapitalAccountId,
            'contact_person' => 'Demo Director',
            'phone' => '0700000000',
            'email' => 'demo@example.com',
            'is_active' => true,
            'notes' => 'Sample funding source generated by LelekweProjectsAccountingDemoSeeder.',
        ]);
    }

    /** @return array<string, int> */
    private function seedProjectFunds(int $fundingSourceId, int $projectCostCenterId): array
    {
        return [
            'cctv' => $this->upsert('accounting_project_funds', ['fund_code' => 'DEMO-FUND-CCTV-2026'], [
                'fund_code' => 'DEMO-FUND-CCTV-2026',
                'name' => 'DEMO CCTV Security Expansion Fund',
                'description' => 'Sample financing record for the CCTV security expansion project.',
                'funding_source_id' => $fundingSourceId,
                'cost_center_id' => $projectCostCenterId,
                'manager_id' => $this->userId,
                'project_type' => 'cctv',
                'budget_amount' => 900000,
                'received_amount' => 0,
                'spent_amount' => 0,
                'balance_amount' => 0,
                'start_date' => now()->subDays(35)->toDateString(),
                'expected_end_date' => now()->addDays(45)->toDateString(),
                'status' => 'active',
                'metadata' => ['demo' => true],
            ]),
            'water' => $this->upsert('accounting_project_funds', ['fund_code' => 'DEMO-FUND-WATER-2026'], [
                'fund_code' => 'DEMO-FUND-WATER-2026',
                'name' => 'DEMO Borehole & Water Reticulation Fund',
                'description' => 'Sample financing record for water infrastructure works.',
                'funding_source_id' => $fundingSourceId,
                'cost_center_id' => $projectCostCenterId,
                'manager_id' => $this->userId,
                'project_type' => 'water',
                'budget_amount' => 650000,
                'received_amount' => 0,
                'spent_amount' => 0,
                'balance_amount' => 0,
                'start_date' => now()->subDays(21)->toDateString(),
                'expected_end_date' => now()->addDays(60)->toDateString(),
                'status' => 'active',
                'metadata' => ['demo' => true],
            ]),
        ];
    }

    /** @param array<string,int> $accounts */
    private function seedAccountMappings(array $accounts): void
    {
        $mappings = [
            ['demo_bank_account', 'DEMO Main Bank Account', 'demo', $accounts['bank']],
            ['demo_project_expense', 'DEMO Project Materials Expense', 'demo', $accounts['project_expense']],
            ['demo_director_capital', 'DEMO Director Capital', 'demo', $accounts['director_capital']],
            ['demo_farm_sales', 'DEMO Farm Sales Income', 'demo', $accounts['farm_sales']],
        ];

        foreach ($mappings as [$key, $label, $module, $accountId]) {
            $this->upsert('accounting_account_mappings', ['key' => $key], [
                'key' => $key,
                'label' => $label,
                'module' => $module,
                'account_id' => $accountId,
                'is_required' => false,
                'description' => 'Demo mapping created for presentation data.',
            ]);
        }
    }

    private function seedTaxSettings(): void
    {
        $settings = [
            ['DEMO-VAT-16', 'DEMO VAT Standard Rate', 'vat', 16.0000],
            ['DEMO-SHA', 'DEMO SHA Configuration', 'shif', null],
            ['DEMO-HOUSING', 'DEMO Housing Levy', 'housing_levy', 1.5000],
        ];

        foreach ($settings as [$code, $name, $type, $rate]) {
            $this->upsert('accounting_tax_settings', ['code' => $code], [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'rate' => $rate,
                'fixed_amount' => null,
                'effective_from' => now()->startOfYear()->toDateString(),
                'effective_to' => null,
                'is_active' => true,
                'metadata' => ['demo' => true, 'country' => 'KE'],
            ]);
        }
    }

    /**
     * @param array<string,int> $accounts
     * @param array<string,int> $costCenters
     * @param array<string,int> $projectFunds
     * @return array<string,int>
     */
    private function seedJournalEntries(
        array $accounts,
        array $costCenters,
        int $fiscalYearId,
        int $periodId,
        array $projectFunds
    ): array {
        $entries = [];

        $entries['capital'] = $this->upsertJournal(
            'DEMO-JV-2026-001',
            now()->subDays(32)->toDateString(),
            'DEMO-CAPITAL-001',
            'DEMO director capital introduced for initial project financing.',
            $fiscalYearId,
            $periodId,
            [
                ['account_id' => $accounts['bank'], 'debit' => 1250000, 'credit' => 0, 'description' => 'Director capital received into demo bank account.'],
                ['account_id' => $accounts['director_capital'], 'debit' => 0, 'credit' => 1250000, 'description' => 'Director capital contribution.'],
            ]
        );

        $entries['cctv_expense'] = $this->upsertJournal(
            'DEMO-JV-2026-002',
            now()->subDays(18)->toDateString(),
            'DEMO-CCTV-EXP-001',
            'DEMO CCTV cameras, cabling and installation costs.',
            $fiscalYearId,
            $periodId,
            [
                [
                    'account_id' => $accounts['project_expense'],
                    'cost_center_id' => $costCenters['projects'],
                    'project_fund_id' => $projectFunds['cctv'],
                    'debit' => 245000,
                    'credit' => 0,
                    'description' => 'CCTV equipment and installation cost.',
                ],
                ['account_id' => $accounts['bank'], 'debit' => 0, 'credit' => 245000, 'description' => 'Paid from demo bank account.'],
            ]
        );

        $entries['water_expense'] = $this->upsertJournal(
            'DEMO-JV-2026-003',
            now()->subDays(11)->toDateString(),
            'DEMO-WATER-EXP-001',
            'DEMO borehole connection, piping and water reticulation costs.',
            $fiscalYearId,
            $periodId,
            [
                [
                    'account_id' => $accounts['project_expense'],
                    'cost_center_id' => $costCenters['projects'],
                    'project_fund_id' => $projectFunds['water'],
                    'debit' => 125000,
                    'credit' => 0,
                    'description' => 'Water infrastructure materials and labour.',
                ],
                ['account_id' => $accounts['bank'], 'debit' => 0, 'credit' => 125000, 'description' => 'Paid from demo bank account.'],
            ]
        );

        $entries['sales'] = $this->upsertJournal(
            'DEMO-JV-2026-004',
            now()->subDays(7)->toDateString(),
            'DEMO-SALES-001',
            'DEMO livestock and farm produce sales received through bank.',
            $fiscalYearId,
            $periodId,
            [
                ['account_id' => $accounts['bank'], 'debit' => 168500, 'credit' => 0, 'description' => 'Demo sales receipt received through bank.'],
                ['account_id' => $accounts['farm_sales'], 'debit' => 0, 'credit' => 168500, 'description' => 'Demo farm sales income.'],
            ]
        );

        $entries['fuel'] = $this->upsertJournal(
            'DEMO-JV-2026-005',
            now()->subDays(4)->toDateString(),
            'DEMO-FUEL-001',
            'DEMO fuel and transport costs for farm operations.',
            $fiscalYearId,
            $periodId,
            [
                [
                    'account_id' => $accounts['fuel_expense'],
                    'cost_center_id' => $costCenters['admin'],
                    'debit' => 47500,
                    'credit' => 0,
                    'description' => 'Demo diesel, transport and delivery costs.',
                ],
                ['account_id' => $accounts['bank'], 'debit' => 0, 'credit' => 47500, 'description' => 'Paid from demo bank account.'],
            ]
        );

        return $entries;
    }

    /** @param array<string,int> $projectFunds @param array<string,int> $entries */
    private function seedProjectFundTransactions(array $projectFunds, int $fundingSourceId, array $entries): void
    {
        $transactions = [
            ['DEMO-PFT-CCTV-R01', $projectFunds['cctv'], $entries['capital'], 'receipt', now()->subDays(32)->toDateString(), 800000, 'bank', 'DEMO-CCTV-RECEIPT', 'Demo director funding received for CCTV security expansion.'],
            ['DEMO-PFT-CCTV-E01', $projectFunds['cctv'], $entries['cctv_expense'], 'expense', now()->subDays(18)->toDateString(), 245000, 'bank', 'DEMO-CCTV-EXPENSE', 'Demo CCTV equipment and installation expense.'],
            ['DEMO-PFT-WATER-R01', $projectFunds['water'], $entries['capital'], 'receipt', now()->subDays(32)->toDateString(), 450000, 'bank', 'DEMO-WATER-RECEIPT', 'Demo director funding received for water infrastructure.'],
            ['DEMO-PFT-WATER-E01', $projectFunds['water'], $entries['water_expense'], 'expense', now()->subDays(11)->toDateString(), 125000, 'bank', 'DEMO-WATER-EXPENSE', 'Demo borehole and water reticulation expense.'],
        ];

        foreach ($transactions as [$number, $fundId, $journalId, $type, $date, $amount, $method, $reference, $narration]) {
            $this->upsert('accounting_project_fund_transactions', ['transaction_number' => $number], [
                'transaction_number' => $number,
                'project_fund_id' => $fundId,
                'funding_source_id' => $fundingSourceId,
                'journal_entry_id' => $journalId,
                'transaction_type' => $type,
                'transaction_date' => $date,
                'amount' => $amount,
                'payment_method' => $method,
                'reference' => $reference,
                'narration' => $narration,
                'created_by' => $this->userId,
                'approved_by' => $this->userId,
                'approved_at' => now(),
                'metadata' => ['demo' => true],
            ]);
        }
    }

    /** @param array<string,int> $projectFunds */
    private function syncProjectFundBalances(array $projectFunds): void
    {
        foreach ($projectFunds as $fundId) {
            $received = (float) DB::table('accounting_project_fund_transactions')
                ->where('project_fund_id', $fundId)
                ->whereIn('transaction_type', ['receipt', 'allocation'])
                ->sum('amount');

            $spent = (float) DB::table('accounting_project_fund_transactions')
                ->where('project_fund_id', $fundId)
                ->where('transaction_type', 'expense')
                ->sum('amount');

            $refunds = (float) DB::table('accounting_project_fund_transactions')
                ->where('project_fund_id', $fundId)
                ->where('transaction_type', 'refund')
                ->sum('amount');

            $adjustments = (float) DB::table('accounting_project_fund_transactions')
                ->where('project_fund_id', $fundId)
                ->where('transaction_type', 'adjustment')
                ->sum('amount');

            DB::table('accounting_project_funds')
                ->where('id', $fundId)
                ->update([
                    'received_amount' => $received,
                    'spent_amount' => $spent,
                    'balance_amount' => $received - $spent - $refunds + $adjustments,
                    'updated_at' => now(),
                ]);
        }
    }

    /** @param array<string,int> $journalEntries */
    private function seedDemoReconciliation(int $bankAccountId, array $journalEntries): void
    {
        $systemBalance = 1001000.00;

        $this->upsert('accounting_reconciliations', ['reconciliation_number' => 'DEMO-REC-2026-001'], [
            'reconciliation_number' => 'DEMO-REC-2026-001',
            'account_id' => $bankAccountId,
            'statement_date' => now()->subDays(1)->toDateString(),
            'statement_balance' => $systemBalance,
            'system_balance' => $systemBalance,
            'difference' => 0,
            'status' => 'reconciled',
            'notes' => 'Demo reconciliation based on the seeded accounting entries.',
            'created_by' => $this->userId,
            'reconciled_by' => $this->userId,
            'reconciled_at' => now(),
        ]);
    }

    private function seedProjectsAndWorks(): void
    {
        if (! Schema::hasTable('project_categories') || ! Schema::hasTable('farm_projects')) {
            $this->command?->warn('Projects & Works tables were not found. Accounting demo data was still seeded.');
            return;
        }

        try {
            $categories = [
                'security' => $this->upsert('project_categories', ['code' => 'DEMO-CCTV'], [
                    'name' => 'DEMO Security & CCTV',
                    'code' => 'DEMO-CCTV',
                    'type' => 'security',
                    'icon' => 'heroicon-o-video-camera',
                    'color' => '#0f766e',
                    'is_active' => true,
                    'description' => 'Sample category for CCTV, access and farm security works.',
                    'created_by' => $this->userId,
                ]),
                'water' => $this->upsert('project_categories', ['code' => 'DEMO-WATER'], [
                    'name' => 'DEMO Water Works',
                    'code' => 'DEMO-WATER',
                    'type' => 'dam',
                    'icon' => 'heroicon-o-beaker',
                    'color' => '#0369a1',
                    'is_active' => true,
                    'description' => 'Sample category for boreholes, piping and water reticulation.',
                    'created_by' => $this->userId,
                ]),
                'crop' => $this->upsert('project_categories', ['code' => 'DEMO-CROP'], [
                    'name' => 'DEMO Crop Development',
                    'code' => 'DEMO-CROP',
                    'type' => 'land_preparation',
                    'icon' => 'heroicon-o-sparkles',
                    'color' => '#4d7c0f',
                    'is_active' => true,
                    'description' => 'Sample category for fodder, crop and land preparation works.',
                    'created_by' => $this->userId,
                ]),
            ];

            $projects = [
                'cctv' => $this->upsert('farm_projects', ['project_number' => 'DEMO-CCTV-2026'], [
                    'project_number' => 'DEMO-CCTV-2026',
                    'name' => 'DEMO Farm Security CCTV Expansion',
                    'project_category_id' => $categories['security'],
                    'project_type' => 'security',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'location' => 'Main gate, livestock yards and stores',
                    'land_area' => 0,
                    'land_area_unit' => 'acres',
                    'description' => 'Sample project for a 16-channel CCTV expansion covering gate access, livestock yards and stores.',
                    'objectives' => 'Improve perimeter visibility, farm security and incident traceability.',
                    'scope_of_work' => 'Cameras, poles, network cabling, PoE switching, NVR configuration and commissioning.',
                    'start_date' => now()->subDays(35)->toDateString(),
                    'expected_end_date' => now()->addDays(20)->toDateString(),
                    'progress_percent' => 68,
                    'budget_amount' => 900000,
                    'approved_budget_amount' => 850000,
                    'committed_amount' => 420000,
                    'spent_amount' => 245000,
                    'balance_amount' => 605000,
                    'contractor_name' => 'DEMO Secure Systems Kenya',
                    'contractor_phone' => '0700000001',
                    'contractor_email' => 'security-demo@example.com',
                    'manager_id' => $this->userId,
                    'notes' => 'Demo record — remove before operational go-live.',
                    'created_by' => $this->userId,
                    'approved_by' => $this->userId,
                    'approved_at' => now()->subDays(30),
                ]),
                'water' => $this->upsert('farm_projects', ['project_number' => 'DEMO-WATER-2026'], [
                    'project_number' => 'DEMO-WATER-2026',
                    'name' => 'DEMO Borehole & Water Reticulation Upgrade',
                    'project_category_id' => $categories['water'],
                    'project_type' => 'plumbing',
                    'priority' => 'urgent',
                    'status' => 'in_progress',
                    'location' => 'Borehole line, cattle troughs and lower paddocks',
                    'land_area' => 28,
                    'land_area_unit' => 'acres',
                    'description' => 'Sample water project covering pump connections, storage, pipeline extensions and livestock troughs.',
                    'objectives' => 'Provide reliable water access for livestock, crops and farm staff operations.',
                    'scope_of_work' => 'Pump electrical connection, HDPE pipeline, tanks, troughs and testing.',
                    'start_date' => now()->subDays(21)->toDateString(),
                    'expected_end_date' => now()->addDays(42)->toDateString(),
                    'progress_percent' => 42,
                    'budget_amount' => 650000,
                    'approved_budget_amount' => 620000,
                    'committed_amount' => 230000,
                    'spent_amount' => 125000,
                    'balance_amount' => 495000,
                    'contractor_name' => 'DEMO Water Works Ltd',
                    'contractor_phone' => '0700000002',
                    'contractor_email' => 'water-demo@example.com',
                    'manager_id' => $this->userId,
                    'notes' => 'Demo record — remove before operational go-live.',
                    'created_by' => $this->userId,
                    'approved_by' => $this->userId,
                    'approved_at' => now()->subDays(18),
                ]),
                'napier' => $this->upsert('farm_projects', ['project_number' => 'DEMO-NAPIER-2026'], [
                    'project_number' => 'DEMO-NAPIER-2026',
                    'name' => 'DEMO Napier Establishment Block',
                    'project_category_id' => $categories['crop'],
                    'project_type' => 'land_preparation',
                    'priority' => 'medium',
                    'status' => 'approved',
                    'location' => 'Lower fodder block',
                    'land_area' => 6,
                    'land_area_unit' => 'acres',
                    'description' => 'Sample fodder-development project for land preparation, droppers, planting and early care.',
                    'objectives' => 'Increase reliable on-farm fodder availability and reduce purchased feed dependence.',
                    'scope_of_work' => 'Land preparation, planting material, labour, manure application and first-weeding cycle.',
                    'start_date' => now()->addDays(5)->toDateString(),
                    'expected_end_date' => now()->addDays(70)->toDateString(),
                    'progress_percent' => 12,
                    'budget_amount' => 280000,
                    'approved_budget_amount' => 275000,
                    'committed_amount' => 0,
                    'spent_amount' => 0,
                    'balance_amount' => 275000,
                    'contractor_name' => 'DEMO Farm Labour Team',
                    'contractor_phone' => '0700000003',
                    'contractor_email' => 'crops-demo@example.com',
                    'manager_id' => $this->userId,
                    'notes' => 'Demo record — remove before operational go-live.',
                    'created_by' => $this->userId,
                    'approved_by' => $this->userId,
                    'approved_at' => now()->subDays(2),
                ]),
            ];

            $this->seedOptionalProjectRelations($projects);
        } catch (Throwable $e) {
            $this->command?->warn('Projects & Works demo data skipped: ' . $e->getMessage());
        }
    }

    /** @param array<string,int> $projects */
    private function seedOptionalProjectRelations(array $projects): void
    {
        $this->seedOptionalProjectRow(
            'project_milestones',
            $projects['cctv'],
            'CCTV installation and commissioning',
            'Complete camera installation, network setup, NVR configuration and handover.',
            'in_progress',
            68
        );

        $this->seedOptionalProjectRow(
            'project_tasks',
            $projects['water'],
            'Lay pipeline from storage tanks to livestock troughs',
            'Install HDPE piping, valves and trough connections for lower paddocks.',
            'in_progress',
            42
        );

        $this->seedOptionalProjectRow(
            'project_budget_lines',
            $projects['cctv'],
            'CCTV equipment and installation package',
            'Sample budget line for cameras, PoE networking, cabling and installation.',
            'approved',
            0,
            420000
        );

        $this->seedOptionalProjectRow(
            'project_expenses',
            $projects['water'],
            'Borehole piping and fittings',
            'Sample project expense for water reticulation materials.',
            'approved',
            0,
            125000
        );

        $this->seedOptionalProjectRow(
            'project_progress_updates',
            $projects['cctv'],
            'Security system installation is progressing',
            'Sample progress update: perimeter poles installed and main cabling underway.',
            'in_progress',
            68
        );
    }

    private function seedOptionalProjectRow(
        string $table,
        int $projectId,
        string $title,
        string $description,
        string $status,
        int $progress = 0,
        ?float $amount = null
    ): void {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            $projectKey = $this->firstExistingColumn($table, ['farm_project_id', 'project_id']);
            $titleKey = $this->firstExistingColumn($table, ['title', 'name', 'description', 'details', 'narration']);

            if (! $projectKey || ! $titleKey) {
                return;
            }

            $payload = [$projectKey => $projectId, $titleKey => $title];

            $this->putFirstExisting($table, $payload, ['description', 'details', 'notes', 'narration'], $description);
            $this->putFirstExisting($table, $payload, ['status', 'approval_status'], $status);
            $this->putFirstExisting($table, $payload, ['progress_percent', 'progress'], $progress);
            $this->putFirstExisting($table, $payload, ['start_date', 'planned_date', 'date'], now()->subDays(8)->toDateString());
            $this->putFirstExisting($table, $payload, ['due_date', 'target_date', 'expected_end_date', 'completion_date'], now()->addDays(21)->toDateString());
            $this->putFirstExisting($table, $payload, ['created_by', 'manager_id', 'assigned_to'], $this->userId);

            if ($amount !== null) {
                $this->putFirstExisting($table, $payload, ['amount', 'total_amount', 'budget_amount', 'approved_amount', 'spent_amount', 'estimated_amount'], $amount);
                $this->putFirstExisting($table, $payload, ['quantity'], 1);
                $this->putFirstExisting($table, $payload, ['unit_cost'], $amount);
            }

            $this->upsert($table, [$projectKey => $projectId, $titleKey => $title], $payload);
        } catch (Throwable $e) {
            $this->command?->warn("Optional {$table} demo row skipped: {$e->getMessage()}");
        }
    }

    /**
     * @param array<int,array<string,mixed>> $lines
     */
    private function upsertJournal(
        string $journalNumber,
        string $date,
        string $reference,
        string $narration,
        int $fiscalYearId,
        int $periodId,
        array $lines
    ): int {
        $totalDebit = round(array_sum(array_map(fn (array $line): float => (float) ($line['debit'] ?? 0), $lines)), 2);
        $totalCredit = round(array_sum(array_map(fn (array $line): float => (float) ($line['credit'] ?? 0), $lines)), 2);

        if (abs($totalDebit - $totalCredit) > 0.009) {
            throw new \LogicException("Demo journal {$journalNumber} is not balanced.");
        }

        $journalId = $this->upsert('accounting_journal_entries', ['journal_number' => $journalNumber], [
            'journal_number' => $journalNumber,
            'fiscal_year_id' => $fiscalYearId,
            'accounting_period_id' => $periodId,
            'transaction_date' => $date,
            'source_type' => 'demo_seed',
            'source_id' => null,
            'reference' => $reference,
            'narration' => $narration,
            'status' => 'posted',
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'created_by' => $this->userId,
            'posted_by' => $this->userId,
            'posted_at' => now(),
            'metadata' => ['demo' => true],
        ]);

        DB::table('accounting_journal_entry_lines')
            ->where('journal_entry_id', $journalId)
            ->delete();

        foreach ($lines as $line) {
            $this->upsert('accounting_journal_entry_lines', [
                'journal_entry_id' => $journalId,
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? null,
            ], [
                'journal_entry_id' => $journalId,
                'account_id' => $line['account_id'],
                'cost_center_id' => $line['cost_center_id'] ?? null,
                'project_fund_id' => $line['project_fund_id'] ?? null,
                'description' => $line['description'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'party_type' => null,
                'party_id' => null,
                'metadata' => ['demo' => true],
            ]);
        }

        return $journalId;
    }

    /**
     * Update-or-create while tolerating minor schema variations between current project tables.
     *
     * @param array<string,mixed> $where
     * @param array<string,mixed> $data
     */
    private function upsert(string $table, array $where, array $data): int
    {
        $now = now();
        $data = $this->filterExistingColumns($table, $data);
        $where = $this->filterExistingColumns($table, $where);

        if ($where === []) {
            throw new \InvalidArgumentException("No usable identity fields supplied for {$table}.");
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = $now;
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $data['deleted_at'] = null;
        }

        $query = DB::table($table)->where($where);
        $existing = $query->first();

        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update($data);
            return (int) $existing->id;
        }

        if (Schema::hasColumn($table, 'created_at')) {
            $data['created_at'] = $now;
        }

        return (int) DB::table($table)->insertGetId(array_merge($where, $data));
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function filterExistingColumns(string $table, array $data): array
    {
        return array_filter(
            $data,
            fn ($value, string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /** @param array<string,mixed> $payload @param array<int,string> $columns */
    private function putFirstExisting(string $table, array &$payload, array $columns, mixed $value): void
    {
        $column = $this->firstExistingColumn($table, $columns);

        if ($column !== null) {
            $payload[$column] = $value;
        }
    }

    /** @param array<int,string> $columns */
    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
