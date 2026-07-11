<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingTaxSetting;
use App\Models\Finance\ExpenseCategory;
use App\Models\HR\PayrollStatutoryRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KenyaPayrollExpenseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->accounts();
            $this->mappings();
            $this->rates();
            $this->taxSettings();
            $this->categories();
            $this->permissions();
        });
    }

    private function accounts(): void
    {
        $accounts = [
            ['1150', 'Salary Advances Receivable', 'asset', 'debit', '1000', 'current_assets'],
            ['2250', 'Other Payroll Deductions Payable', 'liability', 'credit', '2000', 'current_liabilities'],
            ['6120', 'Employer NSSF Expense', 'expense', 'debit', '6000', 'employee_costs'],
            ['6130', 'Employer Affordable Housing Levy Expense', 'expense', 'debit', '6000', 'employee_costs'],
            ['6220', 'Rent and Rates', 'expense', 'debit', '6000', 'operating_expenses'],
            ['6420', 'Water Expense', 'expense', 'debit', '6000', 'operating_expenses'],
            ['6510', 'Insurance Expense', 'expense', 'debit', '6000', 'operating_expenses'],
            ['6520', 'Licences and Permits', 'expense', 'debit', '6000', 'administrative_expenses'],
            ['6530', 'Cleaning and Sanitation', 'expense', 'debit', '6000', 'operating_expenses'],
            ['6740', 'General Operating Expense', 'expense', 'debit', '6000', 'operating_expenses'],
        ];

        foreach ($accounts as [$code, $name, $type, $normal, $parent, $group]) {
            AccountingAccount::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normal,
                    'parent_id' => AccountingAccount::query()->where('code', $parent)->value('id'),
                    'reporting_group' => $group,
                    'is_active' => true,
                    'is_system' => false,
                    'is_control_account' => in_array($code, ['1150', '2250'], true),
                    'allow_manual_posting' => true,
                ]
            );
        }
    }

    private function mappings(): void
    {
        $map = [
            'salary_advance_receivable' => ['Salary Advances Receivable', 'payroll', '1150'],
            'payroll_other_deductions_payable' => ['Other Payroll Deductions Payable', 'payroll', '2250'],
            'employer_nssf_expense' => ['Employer NSSF Expense', 'payroll', '6120'],
            'employer_housing_levy_expense' => ['Employer AHL Expense', 'payroll', '6130'],
            'rent_expense' => ['Rent and Rates', 'expenses', '6220'],
            'fuel_expense' => ['Fuel and Lubricants', 'expenses', '6200'],
            'transport_expense' => ['Transport Expense', 'expenses', '6210'],
            'repairs_expense' => ['Repairs and Maintenance', 'expenses', '6300'],
            'electricity_expense' => ['Electricity Expense', 'expenses', '6400'],
            'water_expense' => ['Water Expense', 'expenses', '6420'],
            'communication_expense' => ['Internet and Communication', 'expenses', '6410'],
            'security_expense' => ['Security Expense', 'expenses', '6500'],
            'insurance_expense' => ['Insurance Expense', 'expenses', '6510'],
            'licences_expense' => ['Licences and Permits', 'expenses', '6520'],
            'cleaning_expense' => ['Cleaning and Sanitation', 'expenses', '6530'],
            'farm_supplies_expense' => ['Farm Supplies', 'expenses', '6600'],
            'office_supplies_expense' => ['Office Supplies', 'expenses', '6700'],
            'professional_fees_expense' => ['Professional Fees', 'expenses', '6710'],
            'bank_charges_expense' => ['Bank and M-Pesa Charges', 'expenses', '6720'],
            'casual_labour_expense' => ['Casual Labour', 'expenses', '6110'],
            'general_operating_expense' => ['General Operating Expense', 'expenses', '6740'],
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
                    'description' => 'Payroll and operating expense mapping for ' . $label,
                ]
            );
        }
    }

    private function rates(): void
    {
        PayrollStatutoryRate::query()->updateOrCreate(
            ['code' => 'PAYE_CURRENT'],
            [
                'name' => 'Kenya PAYE Current Bands',
                'type' => 'paye',
                'effective_from' => '2023-07-01',
                'personal_relief' => 2400,
                'remittance_due_day' => 9,
                'bands' => [
                    ['amount' => 24000, 'rate' => 10],
                    ['amount' => 8333, 'rate' => 25],
                    ['amount' => 467667, 'rate' => 30],
                    ['amount' => 300000, 'rate' => 32.5],
                    ['amount' => null, 'rate' => 35],
                ],
                'legal_reference' => 'KRA individual income tax bands effective 1 July 2023; review after tax-law changes.',
                'is_active' => true,
            ]
        );

        PayrollStatutoryRate::query()->updateOrCreate(
            ['code' => 'NSSF_YEAR4_2026'],
            [
                'name' => 'NSSF Year 4 Rates (2026)',
                'type' => 'nssf',
                'effective_from' => '2026-02-01',
                'employee_rate' => 6,
                'employer_rate' => 6,
                'lower_earning_limit' => 9000,
                'upper_earning_limit' => 108000,
                'maximum_amount' => 6480,
                'remittance_due_day' => 9,
                'legal_reference' => 'NSSF Year 4 contribution rates effective February 2026.',
                'is_active' => true,
            ]
        );

        PayrollStatutoryRate::query()->updateOrCreate(
            ['code' => 'SHIF_CURRENT'],
            [
                'name' => 'Social Health Insurance Fund',
                'type' => 'shif',
                'effective_from' => '2024-10-01',
                'employee_rate' => 2.75,
                'minimum_amount' => 300,
                'remittance_due_day' => 9,
                'legal_reference' => 'SHIF salaried household contribution rate; review against current SHA regulations.',
                'is_active' => true,
            ]
        );

        PayrollStatutoryRate::query()->updateOrCreate(
            ['code' => 'AHL_CURRENT'],
            [
                'name' => 'Affordable Housing Levy',
                'type' => 'housing_levy',
                'effective_from' => '2024-03-19',
                'employee_rate' => 1.5,
                'employer_rate' => 1.5,
                'remittance_due_day' => 9,
                'legal_reference' => 'Affordable Housing Act 2024; employee and employer each contribute 1.5%.',
                'is_active' => true,
            ]
        );
    }

    private function taxSettings(): void
    {
        $settings = [
            [
                'code' => 'WHT_PROFESSIONAL',
                'name' => 'Management, Professional and Training Fees',
                'resident_rate' => 5,
                'non_resident_rate' => 20,
                'legal_reference' => 'KRA withholding tax schedule. Confirm treaty relief and the supplier classification before posting.',
            ],
            [
                'code' => 'WHT_RENT',
                'name' => 'Commercial Rent Withholding Tax',
                'resident_rate' => 10,
                'non_resident_rate' => 30,
                'legal_reference' => 'KRA withholding tax schedule for rent from immovable property. Confirm whether the payer is required to withhold.',
            ],
            [
                'code' => 'WHT_CONTRACTUAL',
                'name' => 'Contractual Payments Withholding Tax',
                'resident_rate' => 3,
                'non_resident_rate' => 20,
                'legal_reference' => 'KRA withholding tax schedule for contractual payments. Confirm the contract and supplier residency.',
            ],
        ];

        foreach ($settings as $setting) {
            AccountingTaxSetting::query()->updateOrCreate(
                ['code' => $setting['code']],
                [
                    'name' => $setting['name'],
                    'type' => 'withholding',
                    'rate' => $setting['resident_rate'],
                    'resident_rate' => $setting['resident_rate'],
                    'non_resident_rate' => $setting['non_resident_rate'],
                    'effective_from' => '2023-07-01',
                    'is_active' => true,
                    'is_system' => true,
                    'is_default' => false,
                    'filing_frequency' => 'transactional',
                    'remittance_due_days' => 5,
                    'metadata' => [
                        'legal_reference' => $setting['legal_reference'],
                        'due_date_note' => 'Operational due date is estimated as five working days after deduction; confirm public holidays and current KRA rules.',
                    ],
                ]
            );
        }
    }

    private function categories(): void
    {
        $categories = [
            ['RENT', 'Rent and Rates', '6220', 'non_vat', 'WHT_RENT', 10, true],
            ['FUEL', 'Fuel and Lubricants', '6200', 'standard_vat', null, 0, true],
            ['TRANSPORT', 'Transport and Delivery', '6210', 'non_vat', null, 0, false],
            ['REPAIRS', 'Repairs and Maintenance', '6300', 'standard_vat', null, 0, true],
            ['ELECTRICITY', 'Electricity', '6400', 'standard_vat', null, 0, true],
            ['WATER', 'Water', '6420', 'non_vat', null, 0, false],
            ['COMMUNICATION', 'Internet and Communication', '6410', 'standard_vat', null, 0, true],
            ['SECURITY', 'Security', '6500', 'standard_vat', null, 0, true],
            ['INSURANCE', 'Insurance', '6510', 'exempt', null, 0, false],
            ['LICENCES', 'Licences and Permits', '6520', 'non_vat', null, 0, false],
            ['CLEANING', 'Cleaning and Sanitation', '6530', 'standard_vat', null, 0, true],
            ['FARM_SUPPLIES', 'Farm Supplies', '6600', 'standard_vat', null, 0, true],
            ['OFFICE_SUPPLIES', 'Office Supplies', '6700', 'standard_vat', null, 0, true],
            ['PROFESSIONAL', 'Professional Fees', '6710', 'standard_vat', 'WHT_PROFESSIONAL', 5, true],
            ['BANK_CHARGES', 'Bank and M-Pesa Charges', '6720', 'exempt', null, 0, false],
            ['CASUAL_LABOUR', 'Casual Labour', '6110', 'non_vat', null, 0, false],
            ['GENERAL', 'General Operating Expense', '6740', 'non_vat', null, 0, false],
        ];

        foreach ($categories as [$code, $name, $accountCode, $tax, $wht, $rate, $etims]) {
            ExpenseCategory::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'account_id' => AccountingAccount::query()->where('code', $accountCode)->value('id'),
                    'default_tax_treatment' => $tax,
                    'default_wht_code' => $wht,
                    'default_wht_rate' => $rate,
                    'requires_etims' => $etims,
                    'is_active' => true,
                    'description' => 'Configurable operating expense category. Confirm tax treatment per invoice and supplier.',
                ]
            );
        }
    }

    private function permissions(): void
    {
        $payrollPaymentPermissions = [
            'view payroll payments',
            'create payroll payments',
            'edit payroll payments',
            'post payroll payments',
            'reverse payroll payments',
            'delete draft payroll payments',
            'export payroll payments',
        ];

        $statutoryPermissions = [
            'view statutory remittances',
            'create statutory remittances',
            'edit statutory remittances',
            'post statutory remittances',
            'reverse statutory remittances',
            'delete draft statutory remittances',
            'export statutory remittances',
            'view payroll statutory rates',
            'manage payroll statutory rates',
        ];

        $expensePermissions = [
            'view operating expenses',
            'create operating expenses',
            'edit operating expenses',
            'approve operating expenses',
            'pay operating expenses',
            'reverse operating expenses',
            'delete draft operating expenses',
            'export operating expenses',

            'view operating expense payments',
            'create operating expense payments',
            'edit operating expense payments',
            'post operating expense payments',
            'reverse operating expense payments',
            'delete draft operating expense payments',
            'export operating expense payments',

            'view expense categories',
            'create expense categories',
            'edit expense categories',
            'archive expense categories',
            'delete expense categories',
        ];

        $permissions = array_values(array_unique([
            ...$payrollPaymentPermissions,
            ...$statutoryPermissions,
            ...$expensePermissions,
            'run payroll expense doctor',
        ]));

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['Administrator', 'Admin'] as $name) {
            $role = Role::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo($permissions);
        }

        foreach (['Finance', 'Accountant'] as $name) {
            $role = Role::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo($permissions);
        }

        $hrRole = Role::query()
            ->where('name', 'HR')
            ->where('guard_name', 'web')
            ->first();

        $hrRole?->givePermissionTo([
            ...$payrollPaymentPermissions,
            ...$statutoryPermissions,
        ]);

        $viewOnlyPermissions = [
            'view payroll payments',
            'view statutory remittances',
            'view payroll statutory rates',
            'view operating expenses',
            'view operating expense payments',
            'view expense categories',
        ];

        foreach (['Director', 'Manager'] as $name) {
            $role = Role::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo(
                $viewOnlyPermissions
            );
        }

        /*
         * Data Entry Clerk and other operational roles are not granted
         * financial permissions automatically. Grant them deliberately
         * from the Roles & Permissions screen when required.
         */
    }
}
