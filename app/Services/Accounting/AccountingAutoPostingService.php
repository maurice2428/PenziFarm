<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingJournalEntry;

class AccountingAutoPostingService
{
    public function __construct(
        private readonly AccountingService $accounting
    ) {
    }

    public function postSale(array $data): AccountingJournalEntry
    {
        $gross = round((float) ($data['amount'] ?? 0), 2);
        $vat = round((float) ($data['vat_amount'] ?? 0), 2);
        $net = round($gross - $vat, 2);
        $paymentAccount = $this->paymentOrReceivableAccount($data);
        $incomeAccount = $this->accounting->accountFromMapping(
            $data['income_mapping_key'] ?? 'livestock_sales_income'
        );

        $lines = [
            [
                'account_id' => $paymentAccount->id,
                'debit' => $gross,
                'credit' => 0,
                'description' => $data['description'] ?? 'Sale recorded',
                'party_type' => $data['customer_type'] ?? null,
                'party_id' => $data['customer_id'] ?? null,
                'party_pin' => $data['customer_pin'] ?? null,
                'party_name' => $data['customer_name'] ?? null,
                'etims_document_number' => $data['etims_invoice_number'] ?? null,
            ],
            [
                'account_id' => $incomeAccount->id,
                'debit' => 0,
                'credit' => $net,
                'description' => $data['description'] ?? 'Sales income',
                'tax_code' => $data['vat_code'] ?? null,
                'tax_rate' => $data['vat_rate'] ?? null,
                'tax_amount' => $vat,
                'etims_document_number' => $data['etims_invoice_number'] ?? null,
            ],
        ];

        if ($vat > 0) {
            $lines[] = [
                'account_id' => $this->accounting->accountFromMapping('vat_output')->id,
                'debit' => 0,
                'credit' => $vat,
                'description' => 'Output VAT',
                'tax_code' => $data['vat_code'] ?? 'VAT_STANDARD',
                'tax_rate' => $data['vat_rate'] ?? null,
                'tax_amount' => $vat,
                'etims_document_number' => $data['etims_invoice_number'] ?? null,
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' => $data['source_reference'] ?? $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Sales invoice recognition',
            'source_type' => $data['source_type'] ?? 'sales_invoice',
            'source_id' => $data['source_id'] ?? null,
            'source_action' => $data['source_action'] ?? 'recognition',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }

    public function postPurchase(array $data): AccountingJournalEntry
    {
        $gross = round((float) ($data['amount'] ?? 0), 2);
        $vat = round((float) ($data['vat_amount'] ?? 0), 2);
        $net = round($gross - $vat, 2);
        $debitAccount = filled($data['debit_account_id'] ?? null)
            ? AccountingAccount::query()->findOrFail($data['debit_account_id'])
            : $this->accounting->accountFromMapping($data['debit_mapping_key'] ?? 'inventory_asset');
        $creditAccount = ($data['is_credit'] ?? true)
            ? $this->accounting->accountFromMapping('accounts_payable')
            : $this->accounting->accountFromMapping(
                $this->paymentAccountKey($data['payment_method'] ?? 'bank')
            );

        $lines = [
            [
                'account_id' => $debitAccount->id,
                'debit' => $net,
                'credit' => 0,
                'description' => $data['description'] ?? 'Goods or services received',
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'project_fund_id' => $data['project_fund_id'] ?? null,
                'party_type' => $data['supplier_type'] ?? null,
                'party_id' => $data['supplier_id'] ?? null,
                'party_pin' => $data['supplier_pin'] ?? null,
                'party_name' => $data['supplier_name'] ?? null,
                'tax_code' => $data['vat_code'] ?? null,
                'tax_rate' => $data['vat_rate'] ?? null,
                'tax_amount' => $vat,
                'etims_document_number' => $data['etims_invoice_number'] ?? null,
            ],
            [
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $gross,
                'description' => ($data['is_credit'] ?? true)
                    ? 'Supplier payable recognized'
                    : 'Purchase paid',
                'party_type' => $data['supplier_type'] ?? null,
                'party_id' => $data['supplier_id'] ?? null,
                'party_pin' => $data['supplier_pin'] ?? null,
                'party_name' => $data['supplier_name'] ?? null,
            ],
        ];

        if ($vat > 0) {
            $lines[] = [
                'account_id' => $this->accounting->accountFromMapping('vat_input')->id,
                'debit' => $vat,
                'credit' => 0,
                'description' => 'Input VAT',
                'tax_code' => $data['vat_code'] ?? 'VAT_STANDARD',
                'tax_rate' => $data['vat_rate'] ?? null,
                'tax_amount' => $vat,
                'etims_document_number' => $data['etims_invoice_number'] ?? null,
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' => $data['source_reference'] ?? $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Purchase or GRN recognition',
            'source_type' => $data['source_type'] ?? 'purchase_receipt',
            'source_id' => $data['source_id'] ?? null,
            'source_action' => $data['source_action'] ?? 'recognition',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }

    public function postSupplierPayment(array $data): AccountingJournalEntry
    {
        $gross = round((float) ($data['amount'] ?? 0), 2);
        $withholding = round((float) ($data['withholding_tax_amount'] ?? 0), 2);
        $cashPaid = round(max(0, $gross - $withholding), 2);

        $lines = [
            [
                'account_id' => $this->accounting->accountFromMapping('accounts_payable')->id,
                'debit' => $gross,
                'credit' => 0,
                'description' => 'Reduce supplier payable',
                'party_type' => $data['supplier_type'] ?? null,
                'party_id' => $data['supplier_id'] ?? null,
                'party_pin' => $data['supplier_pin'] ?? null,
                'party_name' => $data['supplier_name'] ?? null,
            ],
            [
                'account_id' => $this->accounting->accountFromMapping(
                    $this->paymentAccountKey($data['payment_method'] ?? 'bank')
                )->id,
                'debit' => 0,
                'credit' => $cashPaid,
                'description' => 'Net supplier payment issued',
            ],
        ];

        if ($withholding > 0) {
            $lines[] = [
                'account_id' => $this->accounting->accountFromMapping('withholding_tax_payable')->id,
                'debit' => 0,
                'credit' => $withholding,
                'description' => 'Withholding tax payable to KRA',
                'tax_code' => $data['withholding_tax_code'] ?? null,
                'tax_rate' => $data['withholding_tax_rate'] ?? null,
                'tax_amount' => $withholding,
                'party_pin' => $data['supplier_pin'] ?? null,
                'party_name' => $data['supplier_name'] ?? null,
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' => $data['source_reference'] ?? $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Supplier payment',
            'source_type' => $data['source_type'] ?? 'purchase_order_payment',
            'source_id' => $data['source_id'] ?? null,
            'source_action' => $data['source_action'] ?? 'payment',
            'posting_key' => $data['posting_key'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }

    public function postCustomerReceipt(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' => $data['source_reference'] ?? $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Customer receipt',
            'source_type' => $data['source_type'] ?? 'sales_payment',
            'source_id' => $data['source_id'] ?? null,
            'source_action' => $data['source_action'] ?? 'payment',
            'posting_key' => $data['posting_key'] ?? null,
        ], [
            [
                'account_id' => $this->accounting->accountFromMapping(
                    $this->paymentAccountKey($data['payment_method'] ?? 'bank')
                )->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Customer payment received',
            ],
            [
                'account_id' => $this->accounting->accountFromMapping('accounts_receivable')->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Reduce customer receivable',
                'party_type' => $data['customer_type'] ?? null,
                'party_id' => $data['customer_id'] ?? null,
                'party_pin' => $data['customer_pin'] ?? null,
                'party_name' => $data['customer_name'] ?? null,
            ],
        ], postImmediately: true, autoApprove: true);
    }

    public function postStockAdjustment(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $direction = strtolower((string) ($data['direction'] ?? 'out'));
        $inventory = $this->accounting->accountFromMapping('inventory_asset');

        $lines = $direction === 'in'
            ? [
                [
                    'account_id' => $inventory->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Inventory adjustment increase',
                ],
                [
                    'account_id' => $this->accounting->accountFromMapping('inventory_adjustment_gain')->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Inventory adjustment gain',
                ],
            ]
            : [
                [
                    'account_id' => $this->accounting->accountFromMapping('inventory_adjustment_loss')->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Inventory adjustment loss',
                ],
                [
                    'account_id' => $inventory->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Inventory reduction',
                ],
            ];

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Inventory adjustment',
            'source_type' => $data['source_type'] ?? 'stock_movement',
            'source_id' => $data['source_id'] ?? null,
            'source_action' => $data['source_action'] ?? 'adjustment',
            'posting_key' => $data['posting_key'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }


    public function postPayroll(array $data): AccountingJournalEntry
    {
        $gross = round((float) ($data['gross_pay'] ?? 0), 2);
        $paye = round((float) ($data['paye'] ?? 0), 2);
        $nssfEmployee = round(
            (float) ($data['nssf_employee'] ?? $data['nssf'] ?? 0),
            2
        );
        $nssfEmployer = round(
            (float) ($data['nssf_employer'] ?? 0),
            2
        );
        $shif = round((float) ($data['shif'] ?? 0), 2);
        $housingEmployee = round(
            (float) (
                $data['housing_levy_employee']
                ?? $data['housing_levy']
                ?? 0
            ),
            2
        );
        $housingEmployer = round(
            (float) ($data['housing_levy_employer'] ?? 0),
            2
        );
        $salaryAdvances = round(
            (float) ($data['salary_advance_deductions'] ?? 0),
            2
        );
        $otherDeductions = round(
            (float) ($data['other_deductions'] ?? 0),
            2
        );

        $net = round(
            (float) (
                $data['net_pay']
                ?? (
                    $gross
                    - $paye
                    - $nssfEmployee
                    - $shif
                    - $housingEmployee
                    - $salaryAdvances
                    - $otherDeductions
                )
            ),
            2
        );

        $lines = [
            [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping('salary_expense')
                        ->id,
                'debit' => $gross,
                'credit' => 0,
                'description' => 'Gross payroll expense',
            ],
        ];

        if ($nssfEmployer > 0) {
            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping('employer_nssf_expense')
                        ->id,
                'debit' => $nssfEmployer,
                'credit' => 0,
                'description' => 'Employer NSSF contribution expense',
            ];
        }

        if ($housingEmployer > 0) {
            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            'employer_housing_levy_expense'
                        )
                        ->id,
                'debit' => $housingEmployer,
                'credit' => 0,
                'description' =>
                    'Employer Affordable Housing Levy expense',
            ];
        }

        $lines[] = [
            'account_id' =>
                $this->accounting
                    ->accountFromMapping('salary_payable')
                    ->id,
            'debit' => 0,
            'credit' => $net,
            'description' => 'Net salary payable',
        ];

        foreach ([
            'paye_payable' => $paye,
            'nssf_payable' => $nssfEmployee + $nssfEmployer,
            'shif_payable' => $shif,
            'housing_levy_payable' =>
                $housingEmployee + $housingEmployer,
        ] as $mapping => $value) {
            if ($value <= 0) {
                continue;
            }

            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping($mapping)
                        ->id,
                'debit' => 0,
                'credit' => round($value, 2),
                'description' =>
                    str($mapping)
                        ->replace('_', ' ')
                        ->title()
                        ->toString(),
            ];
        }

        if ($salaryAdvances > 0) {
            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            'salary_advance_receivable'
                        )
                        ->id,
                'debit' => 0,
                'credit' => $salaryAdvances,
                'description' => 'Salary advance recovery',
            ];
        }

        if ($otherDeductions > 0) {
            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            'payroll_other_deductions_payable'
                        )
                        ->id,
                'debit' => 0,
                'credit' => $otherDeductions,
                'description' => 'Other payroll deductions payable',
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' =>
                $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' =>
                $data['source_reference']
                ?? $data['reference']
                ?? null,
            'narration' =>
                $data['narration'] ?? 'Payroll recognition',
            'source_type' => $data['source_type'] ?? 'payroll',
            'source_id' => $data['source_id'] ?? null,
            'source_action' =>
                $data['source_action'] ?? 'recognition',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }

    public function postPayrollPayment(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $creditLines = $data['credit_lines'] ?? [];

        $lines = [
            [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping('salary_payable')
                        ->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Clear salary payable',
            ],
        ];

        foreach ($creditLines as $creditLine) {
            $creditAmount = round(
                (float) ($creditLine['amount'] ?? 0),
                2
            );

            if ($creditAmount <= 0) {
                continue;
            }

            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            $this->paymentAccountKey(
                                $creditLine['payment_method'] ?? 'bank'
                            )
                        )
                        ->id,
                'debit' => 0,
                'credit' => $creditAmount,
                'description' =>
                    $creditLine['description']
                    ?? 'Salary payment',
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' =>
                $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' =>
                $data['source_reference']
                ?? $data['reference']
                ?? null,
            'narration' =>
                $data['narration'] ?? 'Salary payment',
            'source_type' =>
                $data['source_type'] ?? 'payroll_payment',
            'source_id' => $data['source_id'] ?? null,
            'source_action' =>
                $data['source_action'] ?? 'payment',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }

    public function postStatutoryRemittance(
        array $data
    ): AccountingJournalEntry {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        $mapping = match (
            strtolower((string) ($data['statutory_type'] ?? ''))
        ) {
            'paye' => 'paye_payable',
            'nssf' => 'nssf_payable',
            'shif', 'sha' => 'shif_payable',
            'housing_levy', 'ahl' => 'housing_levy_payable',
            default => throw new \InvalidArgumentException(
                'Unsupported statutory remittance type.'
            ),
        };

        return $this->accounting->createJournalEntry([
            'transaction_date' =>
                $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' =>
                $data['source_reference']
                ?? $data['reference']
                ?? null,
            'narration' =>
                $data['narration']
                ?? 'Payroll statutory remittance',
            'source_type' =>
                $data['source_type'] ?? 'statutory_remittance',
            'source_id' => $data['source_id'] ?? null,
            'source_action' =>
                $data['source_action'] ?? 'payment',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], [
            [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping($mapping)
                        ->id,
                'debit' => $amount,
                'credit' => 0,
                'description' =>
                    'Clear '
                    . str($mapping)
                        ->replace('_', ' ')
                        ->title()
                        ->toString(),
            ],
            [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            $this->paymentAccountKey(
                                $data['payment_method'] ?? 'bank'
                            )
                        )
                        ->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Statutory remittance paid',
            ],
        ], postImmediately: true, autoApprove: true);
    }

    public function postOperatingExpense(
        array $data
    ): AccountingJournalEntry {
        $net = round((float) ($data['net_amount'] ?? 0), 2);
        $vat = round((float) ($data['vat_amount'] ?? 0), 2);
        $wht = round(
            (float) ($data['withholding_tax_amount'] ?? 0),
            2
        );
        $payable = round(
            (float) (
                $data['payable_amount']
                ?? ($net + $vat - $wht)
            ),
            2
        );

        $lines = [
            [
                'account_id' => (int) $data['expense_account_id'],
                'cost_center_id' =>
                    $data['cost_center_id'] ?? null,
                'project_fund_id' =>
                    $data['project_fund_id'] ?? null,
                'debit' => $net,
                'credit' => 0,
                'description' =>
                    $data['description'] ?? 'Operating expense',
                'party_type' => $data['party_type'] ?? null,
                'party_id' => $data['party_id'] ?? null,
                'party_pin' => $data['party_pin'] ?? null,
                'party_name' => $data['party_name'] ?? null,
                'etims_document_number' =>
                    $data['etims_invoice_number'] ?? null,
            ],
        ];

        if ($vat > 0) {
            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping('vat_input')
                        ->id,
                'debit' => $vat,
                'credit' => 0,
                'description' => 'Claimable input VAT',
                'tax_code' =>
                    $data['vat_code'] ?? 'VAT_STANDARD',
                'tax_rate' => $data['vat_rate'] ?? 16,
                'tax_amount' => $vat,
                'party_pin' => $data['party_pin'] ?? null,
                'party_name' => $data['party_name'] ?? null,
                'etims_document_number' =>
                    $data['etims_invoice_number'] ?? null,
            ];
        }

        $lines[] = [
            'account_id' =>
                $this->accounting
                    ->accountFromMapping('accounts_payable')
                    ->id,
            'debit' => 0,
            'credit' => $payable,
            'description' => 'Operating expense payable',
            'party_type' => $data['party_type'] ?? null,
            'party_id' => $data['party_id'] ?? null,
            'party_pin' => $data['party_pin'] ?? null,
            'party_name' => $data['party_name'] ?? null,
        ];

        if ($wht > 0) {
            $lines[] = [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            'withholding_tax_payable'
                        )
                        ->id,
                'debit' => 0,
                'credit' => $wht,
                'description' => 'Withholding tax payable to KRA',
                'tax_code' =>
                    $data['withholding_tax_code'] ?? null,
                'tax_rate' =>
                    $data['withholding_tax_rate'] ?? null,
                'tax_amount' => $wht,
                'party_pin' => $data['party_pin'] ?? null,
                'party_name' => $data['party_name'] ?? null,
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' =>
                $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' =>
                $data['source_reference']
                ?? $data['reference']
                ?? null,
            'narration' =>
                $data['narration']
                ?? 'Operating expense recognition',
            'source_type' =>
                $data['source_type'] ?? 'operating_expense',
            'source_id' => $data['source_id'] ?? null,
            'source_action' =>
                $data['source_action'] ?? 'recognition',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, postImmediately: true, autoApprove: true);
    }

    public function postOperatingExpensePayment(
        array $data
    ): AccountingJournalEntry {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return $this->accounting->createJournalEntry([
            'transaction_date' =>
                $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'source_reference' =>
                $data['source_reference']
                ?? $data['reference']
                ?? null,
            'narration' =>
                $data['narration']
                ?? 'Operating expense payment',
            'source_type' =>
                $data['source_type']
                ?? 'operating_expense_payment',
            'source_id' => $data['source_id'] ?? null,
            'source_action' =>
                $data['source_action'] ?? 'payment',
            'posting_key' => $data['posting_key'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], [
            [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping('accounts_payable')
                        ->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Clear operating expense payable',
            ],
            [
                'account_id' =>
                    $this->accounting
                        ->accountFromMapping(
                            $this->paymentAccountKey(
                                $data['payment_method'] ?? 'bank'
                            )
                        )
                        ->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Operating expense paid',
            ],
        ], postImmediately: true, autoApprove: true);
    }

    public function postAssetDepreciation(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Asset depreciation',
            'source_type' => $data['source_type'] ?? 'asset_depreciation',
            'source_id' => $data['source_id'] ?? null,
            'posting_key' => $data['posting_key'] ?? null,
        ], [
            [
                'account_id' => $this->accounting->accountFromMapping('depreciation_expense')->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Depreciation expense',
            ],
            [
                'account_id' => $this->accounting->accountFromMapping('accumulated_depreciation')->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Accumulated depreciation',
            ],
        ], postImmediately: true, autoApprove: true);
    }

    private function paymentOrReceivableAccount(array $data): AccountingAccount
    {
        if (($data['is_credit'] ?? false) === true) {
            return $this->accounting->accountFromMapping('accounts_receivable');
        }

        return $this->accounting->accountFromMapping(
            $this->paymentAccountKey($data['payment_method'] ?? 'bank')
        );
    }

    private function paymentAccountKey(string $method): string
    {
        return match (strtolower($method)) {
            'cash' => 'cash_account',
            'mpesa', 'm-pesa', 'stk', 'mobile_money', 'airtel', 'airtel_money' => 'mpesa_account',
            'petty_cash' => 'petty_cash_account',
            default => 'bank_account',
        };
    }
}
