<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingJournalEntry;
use App\Models\Accounting\AccountingSourcePosting;
use App\Models\Accounting\AccountingTaxSetting;
use App\Models\Accounting\AccountingTaxTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccountingIntegrationPostingService
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly AccountingAutoPostingService $autoPosting,
        private readonly KenyaTaxService $tax,
    ) {
    }

    public function postModel(
        Model $model,
        string $eventName = 'manual'
    ): ?AccountingJournalEntry {
        $table = $model->getTable();
        $sourceId = $model->getKey();

        try {
            return match ($table) {
                'sales_invoices' => $this->postSalesInvoice($model),
                'sales_payments' => $this->postSalesPayment($model),

                // A purchase order is a commitment, not an accounting event.
                'purchase_orders' => null,

                'purchase_order_receipts' =>
                    $this->postPurchaseOrderReceipt($model),
                'purchase_order_payments' =>
                    $this->postPurchaseOrderPayment($model),
                'stock_movements' =>
                    $this->postStockMovement($model),
                'payrolls' => $this->postPayroll($model),
                'payroll_payments' =>
                    $this->postPayrollPayment($model),
                'statutory_remittances' =>
                    $this->postStatutoryRemittance($model),
                'operating_expenses' =>
                    $this->postOperatingExpense($model),
                'operating_expense_payments' =>
                    $this->postOperatingExpensePayment($model),
                'project_expenses' => $this->postProjectExpense($model),
                'animal_feedings' => $this->postAnimalFeedingCost($model),
                'animal_health_records' =>
                    $this->postAnimalHealthCost($model),
                default => null,
            };
        } catch (Throwable $exception) {
            $this->accounting->recordPostingFailure(
                sourceType: $table,
                sourceId: $sourceId,
                action: 'recognition',
                exception: $exception,
                eventName: $eventName,
                metadata: [
                    'model_class' => $model::class,
                    'table' => $table,
                ]
            );

            report($exception);

            return null;
        }
    }

    public function reverseSource(
        Model $model,
        string $reason
    ): ?AccountingJournalEntry {
        $sourceType = $this->canonicalSourceType($model->getTable());
        $sourceId = $this->id($model);

        if (! $sourceId) {
            return null;
        }

        $posting = AccountingSourcePosting::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->with('journalEntry')
            ->first();

        if (! $posting?->journalEntry?->isPosted()) {
            return null;
        }

        $reversal = $this->accounting->reverseJournalEntry(
            $posting->journalEntry,
            $reason
        );

        AccountingTaxTransaction::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->update([
                'status' => 'reversed',
            ]);

        return $reversal;
    }

    public function postSalesInvoice(Model $invoice): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($invoice)) {
            return null;
        }

        $id = $this->id($invoice);

        if (! $id || $this->alreadyPosted('sales_invoice', $id, 'recognition')) {
            return null;
        }

        $gross = $this->amountFrom($invoice, [
            'grand_total',
            'total_amount',
            'invoice_total',
            'net_total',
            'total',
            'amount',
            'sale_price',
            'subtotal',
        ]);

        if ($gross <= 0) {
            $gross = $this->sumChildAmount(
                'sales_invoice_items',
                ['sales_invoice_id', 'invoice_id'],
                $id,
                [
                    'line_total',
                    'total_amount',
                    'total',
                    'subtotal',
                    'amount',
                    'sale_price',
                    'total_price',
                ]
            );
        }

        if ($gross <= 0) {
            return null;
        }

        $vat = $this->amountFrom($invoice, [
            'vat_amount',
            'tax_amount',
            'vat',
            'tax',
        ]);

        $customerId = $this->intFrom($invoice, [
            'customer_id',
            'buyer_id',
            'client_id',
        ]);

        $reference = $this->referenceFrom(
            $invoice,
            [
                'invoice_number',
                'invoice_no',
                'number',
                'reference',
                'order_number',
            ],
            'SALE-' . $id
        );

        $journal = $this->autoPosting->postSale([
            'amount' => $gross,
            'vat_amount' => $vat,
            'is_credit' => true,
            'income_mapping_key' =>
                $this->guessSalesIncomeMapping($invoice, $id),
            'transaction_date' => $this->dateFrom(
                $invoice,
                [
                    'invoice_date',
                    'sale_date',
                    'transaction_date',
                    'date',
                    'created_at',
                ]
            ),
            'reference' => $reference,
            'source_reference' => $reference,
            'narration' =>
                'Sales invoice posted to accounting: ' . $reference,
            'source_type' => 'sales_invoice',
            'source_id' => $id,
            'source_action' => 'recognition',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'sales_invoice',
                    $id,
                    'recognition'
                ),
            'customer_type' => $customerId ? 'customer' : null,
            'customer_id' => $customerId,
            'customer_pin' => $this->firstValue(
                $invoice,
                ['customer_pin', 'buyer_pin', 'kra_pin']
            ),
            'customer_name' => $this->firstValue(
                $invoice,
                ['customer_name', 'buyer_name', 'client_name']
            ),
            'etims_invoice_number' => $this->firstValue(
                $invoice,
                [
                    'etims_invoice_number',
                    'etims_number',
                    'control_unit_invoice_number',
                ]
            ),
            'vat_code' => $vat > 0 ? 'VAT_STANDARD' : 'VAT_ZERO',
            'vat_rate' => $vat > 0
                ? $this->tax->setting('VAT_STANDARD')->rateFor()
                : 0,
            'description' => 'Recognize sales invoice receivable',
            'metadata' => [
                'table' => $invoice->getTable(),
                'auto_posted' => true,
                'etims_required' => true,
            ],
        ]);

        if ($vat > 0) {
            $this->tax->registerJournalTax($journal, [
                'tax_code' => 'VAT_STANDARD',
                'direction' => 'output',
                'taxable_amount' => round($gross - $vat, 2),
                'tax_amount' => $vat,
                'gross_amount' => $gross,
                'party_pin' => $this->firstValue(
                    $invoice,
                    ['customer_pin', 'buyer_pin', 'kra_pin']
                ),
                'party_name' => $this->firstValue(
                    $invoice,
                    ['customer_name', 'buyer_name', 'client_name']
                ),
                'etims_invoice_number' => $this->firstValue(
                    $invoice,
                    [
                        'etims_invoice_number',
                        'etims_number',
                        'control_unit_invoice_number',
                    ]
                ),
            ]);
        }

        return $journal;
    }

    public function postSalesPayment(Model $payment): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($payment)) {
            return null;
        }

        $id = $this->id($payment);

        if (! $id || $this->alreadyPosted('sales_payment', $id, 'payment')) {
            return null;
        }

        $amount = $this->amountFrom($payment, [
            'amount',
            'paid_amount',
            'amount_paid',
            'payment_amount',
            'received_amount',
        ]);

        if ($amount <= 0) {
            return null;
        }

        $invoiceId = $this->intFrom($payment, [
            'sales_invoice_id',
            'invoice_id',
            'sale_invoice_id',
        ]);

        $customerId = $this->intFrom($payment, [
            'customer_id',
            'buyer_id',
            'client_id',
        ]);

        if (! $customerId && $invoiceId && Schema::hasTable('sales_invoices')) {
            $customerId = (int) (
                DB::table('sales_invoices')
                    ->where('id', $invoiceId)
                    ->value('customer_id')
                ?: 0
            );
        }

        $reference = $this->referenceFrom(
            $payment,
            [
                'receipt_number',
                'payment_reference',
                'reference',
                'mpesa_receipt_number',
                'mpesa_receipt',
                'transaction_code',
            ],
            'PAY-' . $id
        );

        return $this->autoPosting->postCustomerReceipt([
            'amount' => $amount,
            'payment_method' => $this->paymentMethodFrom($payment),
            'transaction_date' => $this->dateFrom(
                $payment,
                [
                    'payment_date',
                    'paid_at',
                    'transaction_date',
                    'date',
                    'created_at',
                ]
            ),
            'reference' => $reference,
            'source_reference' => $reference,
            'narration' =>
                'Customer receipt posted to accounting: ' . $reference,
            'source_type' => 'sales_payment',
            'source_id' => $id,
            'source_action' => 'payment',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'sales_payment',
                    $id,
                    'payment'
                ),
            'customer_type' => $customerId ? 'customer' : null,
            'customer_id' => $customerId,
        ]);
    }

    public function postPurchaseOrderReceipt(Model $receipt): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($receipt)) {
            return null;
        }

        $id = $this->id($receipt);

        if (! $id || $this->alreadyPosted('purchase_order_receipt', $id, 'recognition')) {
            return null;
        }

        $gross = $this->amountFrom($receipt, [
            'total_received_value',
            'total_amount',
            'grand_total',
            'amount',
        ]);

        if ($gross <= 0) {
            $gross = $this->sumChildAmount(
                'purchase_order_receipt_items',
                ['purchase_order_receipt_id', 'receipt_id'],
                $id,
                ['line_total', 'total_amount', 'amount']
            );
        }

        if ($gross <= 0) {
            return null;
        }

        $purchaseOrderId = $this->intFrom($receipt, ['purchase_order_id']);
        $supplierId = null;
        $vat = 0.0;
        $supplierPin = null;
        $supplierName = null;
        $etimsNumber = $this->firstValue(
            $receipt,
            ['etims_invoice_number', 'supplier_invoice_no']
        );

        if ($purchaseOrderId && Schema::hasTable('purchase_orders')) {
            $order = DB::table('purchase_orders')
                ->where('id', $purchaseOrderId)
                ->first();

            if ($order) {
                $supplierId = (int) ($order->supplier_id ?? 0) ?: null;
                $vat = round((float) ($order->tax_amount ?? 0), 2);
                $etimsNumber = $etimsNumber
                    ?: ($order->supplier_invoice_number ?? null);
            }
        }

        if ($supplierId && Schema::hasTable('suppliers')) {
            $supplier = DB::table('suppliers')
                ->where('id', $supplierId)
                ->first();
            $supplierPin = $supplier->kra_pin ?? $supplier->pin ?? null;
            $supplierName = $supplier->company_name
                ?? $supplier->name
                ?? null;
        }

        // Do not infer input VAT where the source has no tax evidence.
        $vat = min($vat, $gross);

        $reference = $this->referenceFrom(
            $receipt,
            ['receipt_no', 'grn_number', 'number', 'reference'],
            'GRN-' . $id
        );

        $journal = $this->autoPosting->postPurchase([
            'amount' => $gross,
            'vat_amount' => $vat,
            'is_credit' => true,
            'debit_mapping_key' => 'inventory_asset',
            'transaction_date' => $this->dateFrom(
                $receipt,
                ['received_date', 'transaction_date', 'date', 'created_at']
            ),
            'reference' => $reference,
            'source_reference' => $reference,
            'narration' =>
                'Goods received and supplier payable recognized: '
                . $reference,
            'source_type' => 'purchase_order_receipt',
            'source_id' => $id,
            'source_action' => 'recognition',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'purchase_order_receipt',
                    $id,
                    'recognition'
                ),
            'supplier_type' => $supplierId ? 'supplier' : null,
            'supplier_id' => $supplierId,
            'supplier_pin' => $supplierPin,
            'supplier_name' => $supplierName,
            'etims_invoice_number' => $etimsNumber,
            'vat_code' => $vat > 0 ? 'VAT_STANDARD' : null,
            'vat_rate' => $vat > 0
                ? $this->tax->setting('VAT_STANDARD')->rateFor()
                : null,
            'metadata' => [
                'purchase_order_id' => $purchaseOrderId,
                'tax_evidence_required' => $vat > 0,
            ],
        ]);

        if ($vat > 0) {
            $this->tax->registerJournalTax($journal, [
                'tax_code' => 'VAT_STANDARD',
                'direction' => 'input',
                'taxable_amount' => round($gross - $vat, 2),
                'tax_amount' => $vat,
                'gross_amount' => $gross,
                'party_pin' => $supplierPin,
                'party_name' => $supplierName,
                'etims_invoice_number' => $etimsNumber,
            ]);
        }

        return $journal;
    }

    public function postPurchaseOrderPayment(Model $payment): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($payment)) {
            return null;
        }

        $id = $this->id($payment);

        if (! $id || $this->alreadyPosted('purchase_order_payment', $id, 'payment')) {
            return null;
        }

        $gross = $this->amountFrom($payment, [
            'gross_amount',
            'amount',
            'paid_amount',
            'payment_amount',
        ]);

        if ($gross <= 0) {
            return null;
        }

        $withholding = $this->amountFrom($payment, [
            'withholding_tax_amount',
            'wht_amount',
            'tax_withheld',
        ]);

        $purchaseOrderId = $this->intFrom($payment, ['purchase_order_id']);
        $supplierId = null;
        $supplierPin = null;
        $supplierName = null;

        if ($purchaseOrderId && Schema::hasTable('purchase_orders')) {
            $supplierId = (int) (
                DB::table('purchase_orders')
                    ->where('id', $purchaseOrderId)
                    ->value('supplier_id')
                ?: 0
            ) ?: null;
        }

        if ($supplierId && Schema::hasTable('suppliers')) {
            $supplier = DB::table('suppliers')
                ->where('id', $supplierId)
                ->first();
            $supplierPin = $supplier->kra_pin ?? $supplier->pin ?? null;
            $supplierName = $supplier->company_name
                ?? $supplier->name
                ?? null;
        }

        $reference = $this->referenceFrom(
            $payment,
            [
                'payment_number',
                'mpesa_receipt_number',
                'mpesa_reference',
                'bank_reference',
                'cheque_number',
                'reference',
            ],
            'SPAY-' . $id
        );

        $taxCode = $this->firstValue(
            $payment,
            ['withholding_tax_code', 'wht_code']
        );
        $taxRate = $this->amountFrom(
            $payment,
            ['withholding_tax_rate', 'wht_rate']
        );

        $journal = $this->autoPosting->postSupplierPayment([
            'amount' => $gross,
            'withholding_tax_amount' => $withholding,
            'withholding_tax_code' => $taxCode,
            'withholding_tax_rate' => $taxRate,
            'payment_method' => $this->paymentMethodFrom($payment),
            'transaction_date' => $this->dateFrom(
                $payment,
                ['paid_at', 'payment_date', 'transaction_date', 'created_at']
            ),
            'reference' => $reference,
            'source_reference' => $reference,
            'narration' =>
                'Supplier payment posted to accounting: ' . $reference,
            'source_type' => 'purchase_order_payment',
            'source_id' => $id,
            'source_action' => 'payment',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'purchase_order_payment',
                    $id,
                    'payment'
                ),
            'supplier_type' => $supplierId ? 'supplier' : null,
            'supplier_id' => $supplierId,
            'supplier_pin' => $supplierPin,
            'supplier_name' => $supplierName,
        ]);

        if ($withholding > 0 && filled($taxCode)) {
            $this->tax->registerJournalTax($journal, [
                'tax_code' => $taxCode,
                'direction' => 'withheld',
                'taxable_amount' => $gross,
                'tax_amount' => $withholding,
                'gross_amount' => $gross,
                'tax_rate' => $taxRate,
                'party_pin' => $supplierPin,
                'party_name' => $supplierName,
            ]);
        }

        return $journal;
    }

    public function postStockMovement(Model $movement): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($movement)) {
            return null;
        }

        $type = strtolower((string) $this->firstValue(
            $movement,
            ['type', 'source']
        ));

        // Purchase receipts are accounted for at GRN level to avoid duplicates.
        if (str_contains($type, 'purchase')) {
            return null;
        }

        if (! str_contains($type, 'adjust')) {
            return null;
        }

        $id = $this->id($movement);

        if (! $id || $this->alreadyPosted('stock_movement', $id, 'adjustment')) {
            return null;
        }

        $quantity = abs($this->amountFrom($movement, ['quantity']));
        $unitCost = $this->amountFrom($movement, ['unit_cost']);
        $amount = round(
            $this->amountFrom($movement, ['total_cost'])
                ?: ($quantity * $unitCost),
            2
        );

        if ($amount <= 0) {
            return null;
        }

        $direction = strtolower((string) $this->firstValue(
            $movement,
            ['direction']
        ));
        $reference = $this->referenceFrom(
            $movement,
            ['movement_no', 'reference', 'number'],
            'STM-' . $id
        );

        return $this->autoPosting->postStockAdjustment([
            'amount' => $amount,
            'direction' => $direction,
            'transaction_date' => $this->dateFrom(
                $movement,
                ['movement_date', 'transaction_date', 'created_at']
            ),
            'reference' => $reference,
            'narration' => 'Inventory adjustment: ' . $reference,
            'source_type' => 'stock_movement',
            'source_id' => $id,
            'source_action' => 'adjustment',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'stock_movement',
                    $id,
                    'adjustment'
                ),
        ]);
    }


    public function postPayroll(Model $payroll): ?AccountingJournalEntry
    {
        $status = strtolower(
            $this->stringValue(
                $payroll->getAttribute('status')
            )
        );

        if (! in_array($status, ['approved', 'posted'], true)) {
            return null;
        }

        $id = $this->id($payroll);

        if (! $id || $this->alreadyPosted('payroll', $id, 'recognition')) {
            return null;
        }

        $gross = $this->amountFrom($payroll, ['total_gross']);

        if ($gross <= 0 && method_exists($payroll, 'items')) {
            $gross = (float) $payroll->items()->sum('gross_pay');
        }

        if ($gross <= 0) {
            return null;
        }

        return $this->autoPosting->postPayroll([
            'gross_pay' => $gross,
            'paye' => $this->amountFrom($payroll, ['total_paye']),
            'nssf_employee' =>
                $this->amountFrom(
                    $payroll,
                    ['total_nssf_employee']
                ),
            'nssf_employer' =>
                $this->amountFrom(
                    $payroll,
                    ['total_nssf_employer']
                ),
            'shif' =>
                $this->amountFrom($payroll, ['total_shif']),
            'housing_levy_employee' =>
                $this->amountFrom(
                    $payroll,
                    ['total_housing_levy_employee']
                ),
            'housing_levy_employer' =>
                $this->amountFrom(
                    $payroll,
                    ['total_housing_levy_employer']
                ),
            'salary_advance_deductions' =>
                $this->amountFrom(
                    $payroll,
                    ['total_salary_advance_deductions']
                ),
            'other_deductions' =>
                $this->amountFrom(
                    $payroll,
                    ['total_other_deductions']
                ),
            'net_pay' =>
                $this->amountFrom($payroll, ['total_net']),
            'transaction_date' =>
                $this->dateFrom(
                    $payroll,
                    ['period_end', 'created_at']
                ),
            'reference' =>
                $this->referenceFrom(
                    $payroll,
                    ['payroll_number', 'reference', 'number'],
                    'PAYROLL-' . $id
                ),
            'source_type' => 'payroll',
            'source_id' => $id,
            'source_action' => 'recognition',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'payroll',
                    $id,
                    'recognition'
                ),
        ]);
    }

    public function postPayrollPayment(
        Model $payment
    ): ?AccountingJournalEntry {
        if (
            strtolower(
                $this->stringValue(
                    $payment->getAttribute('status')
                )
            )
            !== 'posted'
        ) {
            return null;
        }

        $id = $this->id($payment);

        if (
            ! $id
            || $this->alreadyPosted(
                'payroll_payment',
                $id,
                'payment'
            )
        ) {
            return null;
        }

        $payment->loadMissing('items');

        $amount = round(
            (float) $payment->items->sum('amount'),
            2
        );

        if ($amount <= 0) {
            return null;
        }

        $creditLines = $payment->items
            ->groupBy('payment_method')
            ->map(function ($items, string $method): array {
                return [
                    'payment_method' => $method,
                    'amount' => round(
                        (float) $items->sum('amount'),
                        2
                    ),
                    'description' =>
                        'Salary payment via '
                        . str($method)
                            ->replace('_', ' ')
                            ->title()
                            ->toString(),
                ];
            })
            ->values()
            ->all();

        return $this->autoPosting->postPayrollPayment([
            'amount' => $amount,
            'credit_lines' => $creditLines,
            'transaction_date' =>
                $payment->getAttribute('payment_date')
                ?? now('Africa/Nairobi'),
            'reference' =>
                $payment->getAttribute('payment_number')
                ?: 'PAYROLL-PAY-' . $id,
            'source_type' => 'payroll_payment',
            'source_id' => $id,
            'source_action' => 'payment',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'payroll_payment',
                    $id,
                    'payment'
                ),
        ]);
    }

    public function postStatutoryRemittance(
        Model $remittance
    ): ?AccountingJournalEntry {
        if (
            strtolower(
                $this->stringValue(
                    $remittance->getAttribute('status')
                )
            )
            !== 'posted'
        ) {
            return null;
        }

        $id = $this->id($remittance);

        if (
            ! $id
            || $this->alreadyPosted(
                'statutory_remittance',
                $id,
                'payment'
            )
        ) {
            return null;
        }

        $amount = $this->amountFrom(
            $remittance,
            ['amount']
        );

        if ($amount <= 0) {
            return null;
        }

        return $this->autoPosting->postStatutoryRemittance([
            'amount' => $amount,
            'statutory_type' =>
                $remittance->getAttribute('statutory_type'),
            'payment_method' =>
                $this->paymentMethodFrom($remittance),
            'transaction_date' =>
                $remittance->getAttribute('payment_date')
                ?? now('Africa/Nairobi'),
            'reference' =>
                $remittance->getAttribute('remittance_number')
                ?: 'STAT-' . $id,
            'source_type' => 'statutory_remittance',
            'source_id' => $id,
            'source_action' => 'payment',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'statutory_remittance',
                    $id,
                    'payment'
                ),
        ]);
    }

    public function postOperatingExpense(
        Model $expense
    ): ?AccountingJournalEntry {
        $status = strtolower(
            $this->stringValue(
                $expense->getAttribute('status')
            )
        );

        if (
            ! in_array(
                $status,
                ['approved', 'partially_paid', 'paid'],
                true
            )
        ) {
            return null;
        }

        $id = $this->id($expense);

        if (
            ! $id
            || $this->alreadyPosted(
                'operating_expense',
                $id,
                'recognition'
            )
        ) {
            return null;
        }

        $expense->loadMissing([
            'category.account',
            'supplier',
        ]);

        if (! $expense->category?->account) {
            return null;
        }

        $claimableVat = $expense->vat_claimable
            ? (float) $expense->vat_amount
            : 0.0;

        $expenseDebit = (float) $expense->net_amount
            + ($expense->vat_claimable
                ? 0.0
                : (float) $expense->vat_amount);

        $journal = $this->autoPosting->postOperatingExpense([
            'expense_account_id' =>
                $expense->category->account_id,
            'cost_center_id' =>
                $expense->getAttribute('cost_center_id'),
            'project_fund_id' =>
                $expense->getAttribute('project_fund_id'),
            'net_amount' => $expenseDebit,
            'vat_amount' => $claimableVat,
            'payable_amount' =>
                $this->amountFrom($expense, ['payable_amount']),
            'withholding_tax_amount' =>
                $this->amountFrom(
                    $expense,
                    ['withholding_tax_amount']
                ),
            'vat_code' =>
                $expense->getAttribute('tax_treatment')
                === 'standard_vat'
                    ? 'VAT_STANDARD'
                    : null,
            'vat_rate' => $expense->getAttribute('vat_rate'),
            'withholding_tax_code' =>
                $expense->getAttribute('withholding_tax_code'),
            'withholding_tax_rate' =>
                $expense->getAttribute('withholding_tax_rate'),
            'party_type' =>
                $expense->supplier
                    ? $expense->supplier::class
                    : null,
            'party_id' => $expense->supplier_id,
            'party_pin' =>
                $expense->getAttribute('supplier_kra_pin')
                ?: $expense->supplier?->kra_pin,
            'party_name' =>
                $expense->supplier?->company_name,
            'etims_invoice_number' =>
                $expense->getAttribute('etims_invoice_number'),
            'description' =>
                $expense->getAttribute('description'),
            'transaction_date' =>
                $expense->getAttribute('expense_date')
                ?? now('Africa/Nairobi'),
            'reference' =>
                $expense->getAttribute('expense_number')
                ?: 'EXP-' . $id,
            'source_type' => 'operating_expense',
            'source_id' => $id,
            'source_action' => 'recognition',
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    'operating_expense',
                    $id,
                    'recognition'
                ),
        ]);

        $this->recordExpenseTaxTransactions(
            $expense,
            $journal
        );

        return $journal;
    }

    public function postOperatingExpensePayment(
        Model $payment
    ): ?AccountingJournalEntry {
        if (
            strtolower(
                $this->stringValue(
                    $payment->getAttribute('status')
                )
            )
            !== 'posted'
        ) {
            return null;
        }

        $id = $this->id($payment);

        if (
            ! $id
            || $this->alreadyPosted(
                'operating_expense_payment',
                $id,
                'payment'
            )
        ) {
            return null;
        }

        $amount = $this->amountFrom($payment, ['amount']);

        if ($amount <= 0) {
            return null;
        }

        return $this->autoPosting
            ->postOperatingExpensePayment([
                'amount' => $amount,
                'payment_method' =>
                    $this->paymentMethodFrom($payment),
                'transaction_date' =>
                    $payment->getAttribute('payment_date')
                    ?? now('Africa/Nairobi'),
                'reference' =>
                    $payment->getAttribute('payment_number')
                    ?: 'EXP-PAY-' . $id,
                'source_type' =>
                    'operating_expense_payment',
                'source_id' => $id,
                'source_action' => 'payment',
                'posting_key' =>
                    $this->accounting->buildPostingKey(
                        'operating_expense_payment',
                        $id,
                        'payment'
                    ),
            ]);
    }

    private function recordExpenseTaxTransactions(
        Model $expense,
        AccountingJournalEntry $journal
    ): void {
        $id = $this->id($expense);

        if (! $id) {
            return;
        }

        $partyName = $expense->supplier?->company_name;
        $partyPin =
            $expense->getAttribute('supplier_kra_pin')
            ?: $expense->supplier?->kra_pin;
        $transactionDate =
            $expense->getAttribute('expense_date')
            ?? now('Africa/Nairobi');
        $taxPointDate = $transactionDate;
        $baseDate = \Illuminate\Support\Carbon::parse(
            $transactionDate,
            'Africa/Nairobi'
        );

        $taxes = [
            [
                'code' => 'VAT_STANDARD',
                'direction' => 'input',
                'rate' => (float) $expense->getAttribute('vat_rate'),
                'taxable' =>
                    (float) $expense->getAttribute('net_amount'),
                'amount' =>
                    $expense->getAttribute('vat_claimable')
                        ? (float) $expense->getAttribute('vat_amount')
                        : 0.0,
                'gross' =>
                    (float) $expense->getAttribute('gross_amount'),
            ],
            [
                'code' =>
                    (string) (
                        $expense->getAttribute(
                            'withholding_tax_code'
                        )
                        ?: 'WHT_PROFESSIONAL'
                    ),
                'direction' => 'withholding',
                'rate' =>
                    (float) $expense->getAttribute(
                        'withholding_tax_rate'
                    ),
                'taxable' =>
                    (float) $expense->getAttribute('net_amount'),
                'amount' =>
                    (float) $expense->getAttribute(
                        'withholding_tax_amount'
                    ),
                'gross' =>
                    (float) $expense->getAttribute('gross_amount'),
            ],
        ];

        foreach ($taxes as $tax) {
            if ($tax['amount'] <= 0) {
                continue;
            }

            $setting = AccountingTaxSetting::query()
                ->where('code', $tax['code'])
                ->first();

            AccountingTaxTransaction::query()->updateOrCreate(
                [
                    'source_type' => 'operating_expense',
                    'source_id' => $id,
                    'tax_code' => $tax['code'],
                ],
                [
                    'tax_number' =>
                        'TAX-EXP-'
                        . $id
                        . '-'
                        . $tax['code'],
                    'tax_setting_id' => $setting?->id,
                    'journal_entry_id' => $journal->id,
                    'transaction_date' => $transactionDate,
                    'tax_point_date' => $taxPointDate,
                    'due_date' => $tax['direction'] === 'withholding'
                        ? $baseDate->copy()->addWeekdays(5)
                        : $baseDate->copy()
                            ->addMonthNoOverflow()
                            ->day(20),
                    'direction' => $tax['direction'],
                    'tax_rate' => $tax['rate'],
                    'taxable_amount' => $tax['taxable'],
                    'tax_amount' => $tax['amount'],
                    'gross_amount' => $tax['gross'],
                    'status' => 'posted',
                    'party_name' => $partyName,
                    'party_pin' => $partyPin,
                    'etims_invoice_number' =>
                        $expense->getAttribute(
                            'etims_invoice_number'
                        ),
                    'created_by' => auth()->id(),
                ]
            );
        }
    }

    public function postProjectExpense(Model $expense): ?AccountingJournalEntry
    {
        return $this->postMappedExpense(
            $expense,
            'project_expense',
            'project_expense',
            ['amount', 'total_amount', 'cost'],
            ['expense_date', 'transaction_date', 'date', 'created_at']
        );
    }

    public function postAnimalFeedingCost(Model $feeding): ?AccountingJournalEntry
    {
        return $this->postMappedExpense(
            $feeding,
            'animal_feeding',
            'feed_cost',
            ['total_cost', 'amount', 'cost'],
            ['feeding_date', 'transaction_date', 'date', 'created_at']
        );
    }

    public function postAnimalHealthCost(Model $health): ?AccountingJournalEntry
    {
        return $this->postMappedExpense(
            $health,
            'animal_health',
            'veterinary_cost',
            ['total_cost', 'amount', 'cost'],
            ['record_date', 'treatment_date', 'date', 'created_at']
        );
    }

    private function postMappedExpense(
        Model $model,
        string $sourceType,
        string $debitMapping,
        array $amountFields,
        array $dateFields
    ): ?AccountingJournalEntry {
        if ($this->shouldSkipByStatus($model)) {
            return null;
        }

        $id = $this->id($model);

        if (! $id || $this->alreadyPosted($sourceType, $id, 'recognition')) {
            return null;
        }

        $amount = $this->amountFrom($model, $amountFields);

        if ($amount <= 0) {
            return null;
        }

        $reference = $this->referenceFrom(
            $model,
            ['reference', 'number', 'expense_number', 'feeding_no'],
            strtoupper($sourceType) . '-' . $id
        );

        return $this->autoPosting->postPurchase([
            'amount' => $amount,
            'vat_amount' => 0,
            'is_credit' => false,
            'debit_mapping_key' => $debitMapping,
            'payment_method' => $this->paymentMethodFrom($model),
            'transaction_date' => $this->dateFrom($model, $dateFields),
            'reference' => $reference,
            'narration' => str($sourceType)->replace('_', ' ')->title()->toString(),
            'source_type' => $sourceType,
            'source_id' => $id,
            'posting_key' =>
                $this->accounting->buildPostingKey(
                    $sourceType,
                    $id,
                    'recognition'
                ),
        ]);
    }

    public function alreadyPosted(
        string $sourceType,
        int|string|null $sourceId,
        string $action = 'recognition'
    ): bool {
        $postingKey = $this->accounting->buildPostingKey(
            $sourceType,
            $sourceId,
            $action
        );

        if (! $postingKey) {
            return false;
        }

        return AccountingSourcePosting::query()
            ->where('posting_key', $postingKey)
            ->whereIn('status', ['draft', 'posted', 'reversed'])
            ->exists()
            || AccountingJournalEntry::query()
                ->withTrashed()
                ->where('posting_key', $postingKey)
                ->exists();
    }

    private function canonicalSourceType(string $table): string
    {
        return match ($table) {
            'sales_invoices' => 'sales_invoice',
            'sales_payments' => 'sales_payment',
            'purchase_order_receipts' => 'purchase_order_receipt',
            'purchase_order_payments' => 'purchase_order_payment',
            'stock_movements' => 'stock_movement',
            'payrolls' => 'payroll',
            'payroll_payments' => 'payroll_payment',
            'statutory_remittances' => 'statutory_remittance',
            'operating_expenses' => 'operating_expense',
            'operating_expense_payments' =>
                'operating_expense_payment',
            default => str($table)->singular()->toString(),
        };
    }

    private function guessSalesIncomeMapping(Model $invoice, int $invoiceId): string
    {
        $text = strtolower((string) $this->firstValue(
            $invoice,
            ['category', 'sale_type', 'invoice_type', 'description', 'notes']
        ));

        if ($text === '' && Schema::hasTable('sales_invoice_items')) {
            $text = strtolower($this->childText(
                'sales_invoice_items',
                ['sales_invoice_id', 'invoice_id'],
                $invoiceId,
                ['description', 'item_type', 'category', 'name']
            ));
        }

        return match (true) {
            str_contains($text, 'milk') => 'milk_sales_income',
            str_contains($text, 'egg') => 'egg_sales_income',
            str_contains($text, 'crop') => 'crop_sales_income',
            str_contains($text, 'seedling'),
            str_contains($text, 'nursery') => 'nursery_sales_income',
            default => 'livestock_sales_income',
        };
    }

    private function shouldSkipByStatus(Model $model): bool
    {
        $status = strtolower((string) $this->firstValue(
            $model,
            ['status', 'payment_status', 'approval_status']
        ));

        return in_array($status, [
            'draft',
            'pending',
            'failed',
            'cancelled',
            'canceled',
            'reversed',
            'void',
            'rejected',
        ], true);
    }

    private function paymentMethodFrom(Model $model): string
    {
        $method = strtolower((string) $this->firstValue(
            $model,
            ['payment_method', 'method', 'payment_mode', 'channel']
        ));

        return match (true) {
            str_contains($method, 'cash') => 'cash',
            str_contains($method, 'mpesa'),
            str_contains($method, 'm-pesa'),
            str_contains($method, 'stk') => 'mpesa',
            str_contains($method, 'petty') => 'petty_cash',
            default => 'bank',
        };
    }

    private function amountFrom(Model $model, array $fields): float
    {
        foreach ($fields as $field) {
            $value = $model->getAttribute($field);

            if (is_numeric($value)) {
                return round((float) $value, 2);
            }
        }

        return 0.0;
    }

    private function intFrom(Model $model, array $fields): ?int
    {
        foreach ($fields as $field) {
            $value = $model->getAttribute($field);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function firstValue(Model $model, array $fields): mixed
    {
        foreach ($fields as $field) {
            $value = $model->getAttribute($field);

            if ($value instanceof \BackedEnum) {
                return $value->value;
            }

            if ($value instanceof \UnitEnum) {
                return $value->name;
            }

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function id(Model $model): ?int
    {
        return is_numeric($model->getKey())
            ? (int) $model->getKey()
            : null;
    }

    private function referenceFrom(
        Model $model,
        array $fields,
        string $fallback
    ): string {
        $value = $this->firstValue($model, $fields);

        $text = $this->stringValue($value);

        return $text !== '' ? $text : $fallback;
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return trim((string) $value->value);
        }

        if ($value instanceof \UnitEnum) {
            return trim($value->name);
        }

        if ($value instanceof \Stringable) {
            return trim((string) $value);
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function dateFrom(Model $model, array $fields): mixed
    {
        return $this->firstValue($model, $fields)
            ?? now('Africa/Nairobi');
    }

    private function sumChildAmount(
        string $table,
        array $foreignKeys,
        int $parentId,
        array $amountFields
    ): float {
        if (! Schema::hasTable($table)) {
            return 0.0;
        }

        $foreignKey = collect($foreignKeys)
            ->first(fn (string $field): bool => Schema::hasColumn($table, $field));

        if (! $foreignKey) {
            return 0.0;
        }

        $amountField = collect($amountFields)
            ->first(fn (string $field): bool => Schema::hasColumn($table, $field));

        if (! $amountField) {
            if (
                Schema::hasColumn($table, 'accepted_quantity')
                && Schema::hasColumn($table, 'unit_cost')
            ) {
                return round((float) DB::table($table)
                    ->where($foreignKey, $parentId)
                    ->selectRaw('SUM(accepted_quantity * unit_cost) as total')
                    ->value('total'), 2);
            }

            return 0.0;
        }

        return round((float) DB::table($table)
            ->where($foreignKey, $parentId)
            ->sum($amountField), 2);
    }

    private function childText(
        string $table,
        array $foreignKeys,
        int $parentId,
        array $textFields
    ): string {
        if (! Schema::hasTable($table)) {
            return '';
        }

        $foreignKey = collect($foreignKeys)
            ->first(fn (string $field): bool => Schema::hasColumn($table, $field));
        $textField = collect($textFields)
            ->first(fn (string $field): bool => Schema::hasColumn($table, $field));

        if (! $foreignKey || ! $textField) {
            return '';
        }

        return DB::table($table)
            ->where($foreignKey, $parentId)
            ->pluck($textField)
            ->filter()
            ->implode(' ');
    }
}
