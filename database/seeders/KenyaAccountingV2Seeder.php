<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingTaxSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KenyaAccountingV2Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->accounts();
            $this->mappings();
            $this->taxRules();
            $this->permissions();
        });
    }

    private function accounts(): void
    {
        $accounts = [
            ['1610', 'Withholding Tax Receivable', 'asset', 'debit', '1000', 'current_assets', 'WHT'],
            ['1620', 'Withholding VAT Receivable', 'asset', 'debit', '1000', 'current_assets', 'WHVAT'],
            ['1700', 'Prepaid Taxes', 'asset', 'debit', '1000', 'current_assets', null],
            ['2320', 'Withholding Tax Payable', 'liability', 'credit', '2000', 'current_liabilities', 'WHT'],
            ['2330', 'Withholding VAT Payable', 'liability', 'credit', '2000', 'current_liabilities', 'WHVAT'],
            ['2340', 'Corporation Tax Payable', 'liability', 'credit', '2000', 'current_liabilities', 'CORP_TAX'],
            ['2350', 'Turnover Tax Payable', 'liability', 'credit', '2000', 'current_liabilities', 'TOT'],
            ['2600', 'Accrued Expenses', 'liability', 'credit', '2000', 'current_liabilities', null],
            ['4950', 'Inventory Adjustment Gain', 'income', 'credit', '4000', 'other_income', null],
            ['6710', 'Professional Fees', 'expense', 'debit', '6000', 'administrative_expenses', 'WHT_PROFESSIONAL'],
            ['6720', 'Bank and M-Pesa Charges', 'expense', 'debit', '6000', 'administrative_expenses', null],
            ['6730', 'Tax Penalties and Interest', 'expense', 'debit', '6000', 'administrative_expenses', null],
            ['6910', 'Inventory Adjustment Loss', 'expense', 'debit', '6000', 'operating_expenses', null],
            ['6990', 'Accounting Suspense Account', 'expense', 'debit', '6000', 'operating_expenses', null],
        ];

        foreach ($accounts as [$code, $name, $type, $normal, $parentCode, $group, $taxCode]) {
            AccountingAccount::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normal,
                    'parent_id' => AccountingAccount::query()->where('code', $parentCode)->value('id'),
                    'reporting_group' => $group,
                    'tax_code' => $taxCode,
                    'is_active' => true,
                    'is_system' => false,
                    'is_control_account' => in_array($code, ['1610','1620','2320','2330','2340','2350','6990'], true),
                    'allow_manual_posting' => $code !== '6990',
                ]
            );
        }

        $groups = [
            '1010' => 'cash_and_cash_equivalents', '1020' => 'cash_and_cash_equivalents',
            '1030' => 'cash_and_cash_equivalents', '1040' => 'cash_and_cash_equivalents',
            '1100' => 'trade_receivables', '1200' => 'inventory', '1210' => 'inventory',
            '1220' => 'inventory', '1230' => 'inventory', '1300' => 'biological_assets',
            '1400' => 'property_plant_equipment', '1410' => 'property_plant_equipment',
            '1420' => 'property_plant_equipment', '1490' => 'accumulated_depreciation',
            '1600' => 'tax_assets', '2100' => 'trade_payables', '2200' => 'payroll_liabilities',
            '2210' => 'statutory_liabilities', '2220' => 'statutory_liabilities',
            '2230' => 'statutory_liabilities', '2240' => 'statutory_liabilities',
            '2300' => 'tax_liabilities', '2310' => 'tax_liabilities', '2400' => 'borrowings',
            '2500' => 'borrowings', '3100' => 'contributed_equity', '3200' => 'contributed_equity',
            '3300' => 'retained_earnings', '3400' => 'current_year_result', '3500' => 'grants',
            '4100' => 'operating_revenue', '4200' => 'operating_revenue', '4300' => 'operating_revenue',
            '4400' => 'operating_revenue', '4500' => 'operating_revenue', '4900' => 'other_income',
            '5100' => 'cost_of_sales', '5200' => 'cost_of_sales', '5300' => 'cost_of_sales',
            '5400' => 'cost_of_sales', '5500' => 'cost_of_sales', '6100' => 'employee_costs',
            '6110' => 'employee_costs', '6200' => 'operating_expenses', '6210' => 'operating_expenses',
            '6300' => 'operating_expenses', '6400' => 'operating_expenses', '6410' => 'administrative_expenses',
            '6500' => 'operating_expenses', '6600' => 'operating_expenses', '6700' => 'administrative_expenses',
            '6800' => 'depreciation', '6900' => 'project_expenses',
        ];

        foreach ($groups as $code => $group) {
            AccountingAccount::query()->where('code', $code)->update(['reporting_group' => $group]);
        }

        AccountingAccount::query()->whereIn('code', ['1000','2000','3000','4000','5000','6000'])
            ->update(['is_control_account' => true, 'allow_manual_posting' => false]);
    }

    private function mappings(): void
    {
        $map = [
            'retained_earnings' => ['Retained Earnings', 'equity', '3300'],
            'withholding_tax_payable' => ['Withholding Tax Payable', 'tax', '2320'],
            'withholding_tax_receivable' => ['Withholding Tax Receivable', 'tax', '1610'],
            'withholding_vat_payable' => ['Withholding VAT Payable', 'tax', '2330'],
            'withholding_vat_receivable' => ['Withholding VAT Receivable', 'tax', '1620'],
            'corporation_tax_payable' => ['Corporation Tax Payable', 'tax', '2340'],
            'turnover_tax_payable' => ['Turnover Tax Payable', 'tax', '2350'],
            'inventory_adjustment_gain' => ['Inventory Adjustment Gain', 'inventory', '4950'],
            'inventory_adjustment_loss' => ['Inventory Adjustment Loss', 'inventory', '6910'],
            'bank_charges' => ['Bank and M-Pesa Charges', 'cashbook', '6720'],
            'professional_fees' => ['Professional Fees', 'purchases', '6710'],
            'suspense_account' => ['Accounting Suspense', 'global', '6990'],
        ];

        foreach ($map as $key => [$label, $module, $code]) {
            AccountingAccountMapping::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'module' => $module,
                    'account_id' => AccountingAccount::query()->where('code', $code)->value('id'),
                    'is_required' => true,
                    'is_active' => true,
                    'description' => 'Accounting Core V2 mapping for ' . $label,
                ]
            );
        }
    }

    private function taxRules(): void
    {
        $rules = [
            ['VAT_STANDARD', 'VAT Standard Rate', 'vat', 'sales', 16, null, null, 'monthly', 20, null, true, true, 'Value Added Tax Act; verify current KRA rate'],
            ['VAT_ZERO', 'VAT Zero Rate', 'vat', 'sales', 0, null, null, 'monthly', 20, null, true, false, 'Value Added Tax Act, zero-rated supplies'],
            ['VAT_EXEMPT', 'VAT Exempt Supply', 'vat', 'sales', 0, null, null, 'monthly', 20, null, false, false, 'Value Added Tax Act, exempt supplies'],
            ['WHT_PROFESSIONAL', 'WHT - Professional Fees', 'withholding', 'payments', null, 5, 20, 'transactional', null, 5, false, true, 'Income Tax Act withholding provisions'],
            ['WHT_MANAGEMENT', 'WHT - Management Fees', 'withholding', 'payments', null, 5, 20, 'transactional', null, 5, false, false, 'Income Tax Act withholding provisions'],
            ['WHT_TRAINING', 'WHT - Training Fees', 'withholding', 'payments', null, 5, 20, 'transactional', null, 5, false, false, 'Income Tax Act withholding provisions'],
            ['WHT_ROYALTIES', 'WHT - Royalties', 'withholding', 'payments', null, 5, 20, 'transactional', null, 5, false, false, 'Income Tax Act withholding provisions'],
            ['WHT_INTEREST', 'WHT - Interest', 'withholding', 'payments', null, 15, 15, 'transactional', null, 5, false, false, 'Income Tax Act withholding provisions'],
            ['WHVAT', 'Withholding VAT', 'withholding_vat', 'payments', 2, null, null, 'transactional', null, 5, false, false, 'Applies only to appointed withholding VAT agents'],
            ['CORPORATION_TAX', 'Resident Corporation Tax', 'corporation_tax', 'corporate', null, 30, 37.5, 'annual', null, null, false, false, 'Income Tax Act; confirm entity residence and exemptions'],
            ['TURNOVER_TAX', 'Turnover Tax', 'turnover_tax', 'sales', 1.5, null, null, 'monthly', 20, null, false, false, 'Income Tax Act; activate only when eligible'],
            ['ETIMS_REQUIRED', 'eTIMS Invoice Evidence', 'other', 'general', 0, null, null, 'transactional', null, null, true, false, 'Electronic Tax Invoice Management System requirements'],
        ];

        foreach ($rules as [$code,$name,$type,$scope,$rate,$resident,$nonResident,$frequency,$returnDay,$remitDays,$etims,$default,$legal]) {
            AccountingTaxSetting::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'tax_scope' => $scope,
                    'rate' => $rate,
                    'resident_rate' => $resident,
                    'non_resident_rate' => $nonResident,
                    'filing_frequency' => $frequency,
                    'return_due_day' => $returnDay,
                    'remittance_due_days' => $remitDays,
                    'legal_reference' => $legal,
                    'requires_etims' => $etims,
                    'is_system' => true,
                    'is_default' => $default,
                    'is_active' => ! in_array($code, ['WHVAT','TURNOVER_TAX'], true),
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'metadata' => ['country' => 'KE', 'review_required' => true],
                ]
            );
        }
    }

    private function permissions(): void
    {
        $permissions = [
            'view accounting opening balances','create accounting opening balances','edit accounting opening balances','delete accounting opening balances','post accounting opening balances',
            'view accounting reconciliations','create accounting reconciliations','edit accounting reconciliations','approve accounting reconciliations','complete accounting reconciliations','reopen accounting reconciliations',
            'view accounting tax transactions','edit accounting tax transactions','view accounting posting failures','retry accounting posting failures','ignore accounting posting failures','view accounting source postings',
            'view cash flow statement','view kenya tax compliance','approve accounting journal entries','post accounting journal entries','reverse accounting journal entries','delete draft accounting journal entries',
            'close accounting periods','lock accounting periods','reopen accounting periods','close accounting fiscal years','lock accounting fiscal years','reopen accounting fiscal years','run accounting backfill','run accounting doctor',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['Administrator','Admin'] as $roleName) {
            if ($role = Role::query()->where('name', $roleName)->where('guard_name','web')->first()) {
                $role->givePermissionTo($permissions);
            }
        }

        foreach (['Finance','Accountant'] as $roleName) {
            if ($role = Role::query()->where('name', $roleName)->where('guard_name','web')->first()) {
                $role->givePermissionTo(array_filter($permissions, fn(string $p): bool => ! str_contains($p, 'run accounting backfill')));
            }
        }
    }
}
