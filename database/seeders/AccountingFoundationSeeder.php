<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingCostCenter;
use App\Models\Accounting\AccountingFiscalYear;
use App\Models\Accounting\AccountingFundingSource;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\AccountingProjectFund;
use App\Models\Accounting\AccountingTaxSetting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingFoundationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedChartOfAccounts();
            $this->seedAccountMappings();
            $this->seedCostCenters();
            $this->seedFiscalYearAndPeriods();
            $this->seedKenyanTaxSettings();
            $this->seedFundingSourcesAndProjects();
        });
    }

    private function seedChartOfAccounts(): void
    {
        $accounts = [
            ['1000', 'Assets', 'asset', 'debit', null, true],
            ['1010', 'Cash in Hand', 'asset', 'debit', '1000', false],
            ['1020', 'Main Bank Account', 'asset', 'debit', '1000', false],
            ['1030', 'M-Pesa Till/Paybill Account', 'asset', 'debit', '1000', false],
            ['1040', 'Petty Cash', 'asset', 'debit', '1000', false],
            ['1100', 'Accounts Receivable', 'asset', 'debit', '1000', false],
            ['1200', 'Inventory - Farm Inputs', 'asset', 'debit', '1000', false],
            ['1210', 'Inventory - Feeds', 'asset', 'debit', '1200', false],
            ['1220', 'Inventory - Veterinary Drugs', 'asset', 'debit', '1200', false],
            ['1230', 'Inventory - Seeds and Fertilizers', 'asset', 'debit', '1200', false],
            ['1300', 'Biological Assets - Livestock', 'asset', 'debit', '1000', false],
            ['1400', 'Farm Equipment and Machinery', 'asset', 'debit', '1000', false],
            ['1410', 'Motor Vehicles', 'asset', 'debit', '1000', false],
            ['1420', 'Buildings and Farm Structures', 'asset', 'debit', '1000', false],
            ['1490', 'Accumulated Depreciation', 'asset', 'credit', '1000', false],
            ['1500', 'Project Fund Control', 'asset', 'debit', '1000', false],
            ['1600', 'VAT Input', 'asset', 'debit', '1000', false],

            ['2000', 'Liabilities', 'liability', 'credit', null, true],
            ['2100', 'Accounts Payable', 'liability', 'credit', '2000', false],
            ['2200', 'Salary Payable', 'liability', 'credit', '2000', false],
            ['2210', 'PAYE Payable', 'liability', 'credit', '2000', false],
            ['2220', 'NSSF Payable', 'liability', 'credit', '2000', false],
            ['2230', 'SHIF/SHA Payable', 'liability', 'credit', '2000', false],
            ['2240', 'Affordable Housing Levy Payable', 'liability', 'credit', '2000', false],
            ['2300', 'VAT Output', 'liability', 'credit', '2000', false],
            ['2310', 'VAT Payable', 'liability', 'credit', '2000', false],
            ['2400', 'Director Loan Payable', 'liability', 'credit', '2000', false],
            ['2500', 'Bank Loans', 'liability', 'credit', '2000', false],

            ['3000', 'Equity', 'equity', 'credit', null, true],
            ['3100', 'Director Capital', 'equity', 'credit', '3000', false],
            ['3200', 'Director Contributions', 'equity', 'credit', '3000', false],
            ['3300', 'Retained Earnings', 'equity', 'credit', '3000', false],
            ['3400', 'Current Year Profit/Loss', 'equity', 'credit', '3000', false],
            ['3500', 'Grant/Donor Funds', 'equity', 'credit', '3000', false],

            ['4000', 'Income', 'income', 'credit', null, true],
            ['4100', 'Livestock Sales Income', 'income', 'credit', '4000', false],
            ['4200', 'Crop Sales Income', 'income', 'credit', '4000', false],
            ['4300', 'Milk Sales Income', 'income', 'credit', '4000', false],
            ['4400', 'Egg Sales Income', 'income', 'credit', '4000', false],
            ['4500', 'Nursery/Seedling Sales Income', 'income', 'credit', '4000', false],
            ['4900', 'Other Farm Income', 'income', 'credit', '4000', false],

            ['5000', 'Cost of Sales', 'cost_of_sales', 'debit', null, true],
            ['5100', 'Cost of Livestock Sold', 'cost_of_sales', 'debit', '5000', false],
            ['5200', 'Crop Production Cost', 'cost_of_sales', 'debit', '5000', false],
            ['5300', 'Feed Cost', 'cost_of_sales', 'debit', '5000', false],
            ['5400', 'Veterinary Cost', 'cost_of_sales', 'debit', '5000', false],
            ['5500', 'Nursery Production Cost', 'cost_of_sales', 'debit', '5000', false],

            ['6000', 'Expenses', 'expense', 'debit', null, true],
            ['6100', 'Salaries and Wages', 'expense', 'debit', '6000', false],
            ['6110', 'Casual Labour', 'expense', 'debit', '6000', false],
            ['6200', 'Fuel Expense', 'expense', 'debit', '6000', false],
            ['6210', 'Transport Expense', 'expense', 'debit', '6000', false],
            ['6300', 'Repairs and Maintenance', 'expense', 'debit', '6000', false],
            ['6400', 'Electricity Expense', 'expense', 'debit', '6000', false],
            ['6410', 'Internet and Communication', 'expense', 'debit', '6000', false],
            ['6500', 'Security Expense', 'expense', 'debit', '6000', false],
            ['6600', 'Farm Supplies Expense', 'expense', 'debit', '6000', false],
            ['6700', 'Office/Admin Expenses', 'expense', 'debit', '6000', false],
            ['6800', 'Depreciation Expense', 'expense', 'debit', '6000', false],
            ['6900', 'Project Expenses', 'expense', 'debit', '6000', false],
        ];

        $created = [];

        foreach ($accounts as $index => [$code, $name, $type, $normalBalance, $parentCode, $isSystem]) {
            $account = AccountingAccount::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'parent_id' => $parentCode ? ($created[$parentCode]?->id ?? AccountingAccount::where('code', $parentCode)->value('id')) : null,
                    'is_active' => true,
                    'is_system' => $isSystem,
                    'sort_order' => $index + 1,
                ]
            );

            $created[$code] = $account;
        }
    }

    private function seedAccountMappings(): void
    {
        $mappings = [
            ['cash_account', 'Cash Account', 'cashbook', '1010'],
            ['bank_account', 'Bank Account', 'cashbook', '1020'],
            ['mpesa_account', 'M-Pesa Account', 'cashbook', '1030'],
            ['petty_cash_account', 'Petty Cash Account', 'cashbook', '1040'],
            ['accounts_receivable', 'Accounts Receivable', 'sales', '1100'],
            ['accounts_payable', 'Accounts Payable', 'purchases', '2100'],
            ['inventory_asset', 'Inventory Asset', 'inventory', '1200'],
            ['feed_inventory', 'Feed Inventory', 'inventory', '1210'],
            ['veterinary_inventory', 'Veterinary Inventory', 'inventory', '1220'],
            ['crop_input_inventory', 'Crop Input Inventory', 'inventory', '1230'],
            ['livestock_sales_income', 'Livestock Sales Income', 'sales', '4100'],
            ['crop_sales_income', 'Crop Sales Income', 'sales', '4200'],
            ['milk_sales_income', 'Milk Sales Income', 'sales', '4300'],
            ['egg_sales_income', 'Egg Sales Income', 'sales', '4400'],
            ['nursery_sales_income', 'Nursery Sales Income', 'sales', '4500'],
            ['feed_cost', 'Feed Cost', 'livestock', '5300'],
            ['veterinary_cost', 'Veterinary Cost', 'livestock', '5400'],
            ['crop_production_cost', 'Crop Production Cost', 'crop', '5200'],
            ['salary_expense', 'Salaries Expense', 'payroll', '6100'],
            ['salary_payable', 'Salary Payable', 'payroll', '2200'],
            ['paye_payable', 'PAYE Payable', 'payroll', '2210'],
            ['nssf_payable', 'NSSF Payable', 'payroll', '2220'],
            ['shif_payable', 'SHIF/SHA Payable', 'payroll', '2230'],
            ['housing_levy_payable', 'Housing Levy Payable', 'payroll', '2240'],
            ['vat_input', 'VAT Input', 'tax', '1600'],
            ['vat_output', 'VAT Output', 'tax', '2300'],
            ['vat_payable', 'VAT Payable', 'tax', '2310'],
            ['director_capital', 'Director Capital', 'funding', '3100'],
            ['director_contribution', 'Director Contributions', 'funding', '3200'],
            ['director_loan_payable', 'Director Loan Payable', 'funding', '2400'],
            ['project_fund_control', 'Project Fund Control', 'projects', '1500'],
            ['depreciation_expense', 'Depreciation Expense', 'assets', '6800'],
            ['accumulated_depreciation', 'Accumulated Depreciation', 'assets', '1490'],
            ['project_expense', 'Project Expenses', 'projects', '6900'],
        ];

        foreach ($mappings as [$key, $label, $module, $accountCode]) {
            AccountingAccountMapping::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'module' => $module,
                    'account_id' => AccountingAccount::where('code', $accountCode)->value('id'),
                    'is_required' => true,
                    'description' => 'Default account mapping for ' . $label,
                ]
            );
        }
    }

    private function seedCostCenters(): void
    {
        $centers = [
            ['ADMIN', 'Administration', 'admin'],
            ['LIVESTOCK', 'Livestock Operations', 'livestock'],
            ['CROPS', 'Crop Farming', 'crop'],
            ['NURSERY', 'Nursery Operations', 'crop'],
            ['ASSETS', 'Farm Assets', 'asset'],
            ['PROJECTS', 'Projects and Infrastructure', 'project'],
        ];

        foreach ($centers as [$code, $name, $type]) {
            AccountingCostCenter::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => $type, 'is_active' => true]
            );
        }
    }

    private function seedFiscalYearAndPeriods(): void
    {
        $year = now()->year;
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        $fiscalYear = AccountingFiscalYear::updateOrCreate(
            ['name' => 'FY ' . $year],
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'open',
                'is_current' => true,
            ]
        );

        for ($month = 1; $month <= 12; $month++) {
            $periodStart = Carbon::create($year, $month, 1);
            $periodEnd = $periodStart->copy()->endOfMonth();

            AccountingPeriod::updateOrCreate(
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'period_number' => $month,
                ],
                [
                    'name' => $periodStart->format('F Y'),
                    'start_date' => $periodStart->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                    'status' => 'open',
                ]
            );
        }
    }

    private function seedKenyanTaxSettings(): void
    {
        $settings = [
            ['VAT_STANDARD', 'VAT Standard Rate', 'vat', 16.0000, null],
            ['VAT_ZERO', 'VAT Zero Rate', 'vat', 0.0000, null],
            ['HOUSING_LEVY_EMPLOYEE', 'Affordable Housing Levy - Employee', 'housing_levy', 1.5000, null],
            ['HOUSING_LEVY_EMPLOYER', 'Affordable Housing Levy - Employer', 'housing_levy', 1.5000, null],
            ['PAYE_CONFIG', 'PAYE Configuration Placeholder', 'paye', null, null],
            ['NSSF_CONFIG', 'NSSF Configuration Placeholder', 'nssf', null, null],
            ['SHIF_CONFIG', 'SHIF/SHA Configuration Placeholder', 'shif', null, null],
        ];

        foreach ($settings as [$code, $name, $type, $rate, $fixed]) {
            AccountingTaxSetting::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'rate' => $rate,
                    'fixed_amount' => $fixed,
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'is_active' => true,
                    'metadata' => ['country' => 'KE'],
                ]
            );
        }
    }

    private function seedFundingSourcesAndProjects(): void
    {
        $directorCapital = AccountingFundingSource::updateOrCreate(
            ['name' => 'Director Capital Contributions'],
            [
                'type' => 'director_capital',
                'linked_account_id' => AccountingAccount::where('code', '3100')->value('id'),
                'is_active' => true,
                'notes' => 'Funds received from directors as capital contribution.',
            ]
        );

        AccountingFundingSource::updateOrCreate(
            ['name' => 'Director Loan Funding'],
            [
                'type' => 'director_loan',
                'linked_account_id' => AccountingAccount::where('code', '2400')->value('id'),
                'is_active' => true,
                'notes' => 'Funds received from directors as repayable loan.',
            ]
        );

        $projectCostCenterId = AccountingCostCenter::where('code', 'PROJECTS')->value('id');

        $projects = [
            ['PRJ-CCTV', 'CCTV Installation Project', 'cctv'],
            ['PRJ-NAPIER', 'Napier Planting Project', 'crop'],
            ['PRJ-PADDOCK', 'Paddocking and Gates Project', 'paddocking'],
            ['PRJ-ROAD', 'Farm Road Improvement Project', 'road'],
        ];

        foreach ($projects as [$code, $name, $type]) {
            AccountingProjectFund::updateOrCreate(
                ['fund_code' => $code],
                [
                    'name' => $name,
                    'description' => 'Default project fund created for ' . $name,
                    'funding_source_id' => $directorCapital->id,
                    'cost_center_id' => $projectCostCenterId,
                    'project_type' => $type,
                    'budget_amount' => 0,
                    'received_amount' => 0,
                    'spent_amount' => 0,
                    'balance_amount' => 0,
                    'status' => 'planned',
                ]
            );
        }
    }
}
