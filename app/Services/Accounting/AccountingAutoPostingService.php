<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingJournalEntry;

class AccountingAutoPostingService
{
    public function __construct(private readonly AccountingService $accounting)
    {
    }

    public function postSale(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $vatAmount = round((float) ($data['vat_amount'] ?? 0), 2);
        $netAmount = round($amount - $vatAmount, 2);
        $paymentAccount = $this->paymentOrReceivableAccount($data);
        $incomeAccount = $this->accounting->accountFromMapping($data['income_mapping_key'] ?? 'livestock_sales_income');

        $lines = [
            [
                'account_id' => $paymentAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => $data['description'] ?? 'Sale recorded',
                'party_type' => $data['customer_type'] ?? null,
                'party_id' => $data['customer_id'] ?? null,
            ],
            [
                'account_id' => $incomeAccount->id,
                'debit' => 0,
                'credit' => $netAmount,
                'description' => $data['description'] ?? 'Sales income',
                'party_type' => $data['customer_type'] ?? null,
                'party_id' => $data['customer_id'] ?? null,
            ],
        ];

        if ($vatAmount > 0) {
            $lines[] = [
                'account_id' => $this->accounting->accountFromMapping('vat_output')->id,
                'debit' => 0,
                'credit' => $vatAmount,
                'description' => 'Output VAT',
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Sale accounting entry',
            'source_type' => $data['source_type'] ?? 'sale',
            'source_id' => $data['source_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, true);
    }

    public function postPurchase(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $vatAmount = round((float) ($data['vat_amount'] ?? 0), 2);
        $netAmount = round($amount - $vatAmount, 2);
        $debitAccount = isset($data['debit_account_id'])
            ? AccountingAccount::findOrFail($data['debit_account_id'])
            : $this->accounting->accountFromMapping($data['debit_mapping_key'] ?? 'project_expense');
        $creditAccount = ($data['is_credit'] ?? false)
            ? $this->accounting->accountFromMapping('accounts_payable')
            : $this->accounting->accountFromMapping($this->paymentAccountKey($data['payment_method'] ?? 'bank'));

        $lines = [
            [
                'account_id' => $debitAccount->id,
                'debit' => $netAmount,
                'credit' => 0,
                'description' => $data['description'] ?? 'Purchase recorded',
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'project_fund_id' => $data['project_fund_id'] ?? null,
                'party_type' => $data['supplier_type'] ?? null,
                'party_id' => $data['supplier_id'] ?? null,
            ],
            [
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => ($data['is_credit'] ?? false) ? 'Supplier bill payable' : 'Purchase payment',
                'party_type' => $data['supplier_type'] ?? null,
                'party_id' => $data['supplier_id'] ?? null,
            ],
        ];

        if ($vatAmount > 0) {
            $lines[] = [
                'account_id' => $this->accounting->accountFromMapping('vat_input')->id,
                'debit' => $vatAmount,
                'credit' => 0,
                'description' => 'Input VAT',
            ];
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Purchase accounting entry',
            'source_type' => $data['source_type'] ?? 'purchase',
            'source_id' => $data['source_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], $lines, true);
    }

    public function postSupplierPayment(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Supplier payment',
            'source_type' => $data['source_type'] ?? 'supplier_payment',
            'source_id' => $data['source_id'] ?? null,
        ], [
            [
                'account_id' => $this->accounting->accountFromMapping('accounts_payable')->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Reduce supplier payable',
                'party_type' => $data['supplier_type'] ?? null,
                'party_id' => $data['supplier_id'] ?? null,
            ],
            [
                'account_id' => $this->accounting->accountFromMapping($this->paymentAccountKey($data['payment_method'] ?? 'bank'))->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Payment issued',
            ],
        ], true);
    }

    public function postCustomerReceipt(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Customer receipt',
            'source_type' => $data['source_type'] ?? 'customer_receipt',
            'source_id' => $data['source_id'] ?? null,
        ], [
            [
                'account_id' => $this->accounting->accountFromMapping($this->paymentAccountKey($data['payment_method'] ?? 'bank'))->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Payment received',
            ],
            [
                'account_id' => $this->accounting->accountFromMapping('accounts_receivable')->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Reduce customer receivable',
                'party_type' => $data['customer_type'] ?? null,
                'party_id' => $data['customer_id'] ?? null,
            ],
        ], true);
    }

    public function postPayroll(array $data): AccountingJournalEntry
    {
        $grossPay = round((float) ($data['gross_pay'] ?? 0), 2);
        $paye = round((float) ($data['paye'] ?? 0), 2);
        $nssf = round((float) ($data['nssf'] ?? 0), 2);
        $shif = round((float) ($data['shif'] ?? 0), 2);
        $housingLevy = round((float) ($data['housing_levy'] ?? 0), 2);
        $netPay = round($grossPay - $paye - $nssf - $shif - $housingLevy, 2);

        $lines = [
            ['account_id' => $this->accounting->accountFromMapping('salary_expense')->id, 'debit' => $grossPay, 'credit' => 0, 'description' => 'Gross salaries'],
            ['account_id' => $this->accounting->accountFromMapping('salary_payable')->id, 'debit' => 0, 'credit' => $netPay, 'description' => 'Net salary payable'],
        ];

        foreach ([
            'paye_payable' => $paye,
            'nssf_payable' => $nssf,
            'shif_payable' => $shif,
            'housing_levy_payable' => $housingLevy,
        ] as $mapping => $amount) {
            if ($amount > 0) {
                $lines[] = ['account_id' => $this->accounting->accountFromMapping($mapping)->id, 'debit' => 0, 'credit' => $amount, 'description' => str_replace('_', ' ', ucfirst($mapping))];
            }
        }

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Payroll posting',
            'source_type' => $data['source_type'] ?? 'payroll',
            'source_id' => $data['source_id'] ?? null,
        ], $lines, true);
    }

    public function postSalaryPayment(array $data): AccountingJournalEntry
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return $this->accounting->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Salary payment',
            'source_type' => $data['source_type'] ?? 'salary_payment',
            'source_id' => $data['source_id'] ?? null,
        ], [
            ['account_id' => $this->accounting->accountFromMapping('salary_payable')->id, 'debit' => $amount, 'credit' => 0, 'description' => 'Clear salary payable'],
            ['account_id' => $this->accounting->accountFromMapping($this->paymentAccountKey($data['payment_method'] ?? 'bank'))->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Salary paid'],
        ], true);
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
        ], [
            ['account_id' => $this->accounting->accountFromMapping('depreciation_expense')->id, 'debit' => $amount, 'credit' => 0, 'description' => 'Depreciation expense'],
            ['account_id' => $this->accounting->accountFromMapping('accumulated_depreciation')->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Accumulated depreciation'],
        ], true);
    }

    private function paymentOrReceivableAccount(array $data): AccountingAccount
    {
        if (($data['is_credit'] ?? false) === true) {
            return $this->accounting->accountFromMapping('accounts_receivable');
        }

        return $this->accounting->accountFromMapping($this->paymentAccountKey($data['payment_method'] ?? 'bank'));
    }

    private function paymentAccountKey(string $method): string
    {
        return match ($method) {
            'cash' => 'cash_account',
            'mpesa' => 'mpesa_account',
            'petty_cash' => 'petty_cash_account',
            default => 'bank_account',
        };
    }
}
