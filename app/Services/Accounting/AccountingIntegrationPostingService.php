<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingJournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccountingIntegrationPostingService
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly AccountingAutoPostingService $autoPosting,
    ) {
    }

    /**
     * Main bridge between existing farm modules and double-entry accounting.
     */
    public function postModel(Model $model, string $eventName = 'manual'): ?AccountingJournalEntry
    {
        $table = $model->getTable();

        try {
            return match ($table) {
                'sales_invoices' => $this->postSalesInvoice($model),
                'sales_payments' => $this->postSalesPayment($model),
                'purchase_orders' => $this->postPurchaseOrder($model),
                'purchase_order_receipts' => $this->postPurchaseOrderReceipt($model),
                'purchase_order_payments' => $this->postPurchaseOrderPayment($model),
                'project_expenses' => $this->postProjectExpense($model),
                'animal_feedings' => $this->postAnimalFeedingCost($model),
                'animal_health_records' => $this->postAnimalHealthCost($model),
                default => null,
            };
        } catch (Throwable $e) {
            Log::error('Accounting auto-posting failed', [
                'table' => $table,
                'model_id' => $model->getKey(),
                'event' => $eventName,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }

    public function postSalesInvoice(Model $invoice): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($invoice)) {
            return null;
        }

        $id = $this->id($invoice);

        if (! $id || $this->alreadyPosted('sales_invoice', $id)) {
            return null;
        }

        $amount = $this->amountFrom($invoice, [
            'grand_total', 'total_amount', 'invoice_total', 'net_total', 'total', 'amount', 'sale_price', 'subtotal',
        ]);

        if ($amount <= 0) {
            $amount = $this->sumChildAmount('sales_invoice_items', ['sales_invoice_id', 'invoice_id'], $id, [
                'line_total', 'total_amount', 'total', 'subtotal', 'amount', 'sale_price', 'total_price',
            ]);
        }

        if ($amount <= 0) {
            return null;
        }

        $vatAmount = $this->amountFrom($invoice, ['vat_amount', 'tax_amount', 'vat', 'tax']);
        $customerId = $this->intFrom($invoice, ['customer_id', 'buyer_id', 'client_id']);
        $reference = $this->referenceFrom($invoice, ['invoice_number', 'invoice_no', 'number', 'reference', 'order_number'], 'SALE-' . $id);

        return $this->autoPosting->postSale([
            'amount' => $amount,
            'vat_amount' => $vatAmount,
            'is_credit' => true,
            'income_mapping_key' => $this->guessSalesIncomeMapping($invoice, $id),
            'transaction_date' => $this->dateFrom($invoice, ['invoice_date', 'sale_date', 'transaction_date', 'date', 'created_at']),
            'reference' => $reference,
            'narration' => 'Sales invoice posted to accounting: ' . $reference,
            'source_type' => 'sales_invoice',
            'source_id' => $id,
            'customer_type' => $customerId ? 'customer' : null,
            'customer_id' => $customerId,
            'description' => 'Recognize sales invoice receivable',
            'metadata' => [
                'table' => $invoice->getTable(),
                'auto_posted' => true,
            ],
        ]);
    }

    public function postSalesPayment(Model $payment): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($payment)) {
            return null;
        }

        $id = $this->id($payment);

        if (! $id || $this->alreadyPosted('sales_payment', $id)) {
            return null;
        }

        $amount = $this->amountFrom($payment, ['amount', 'paid_amount', 'amount_paid', 'payment_amount', 'received_amount']);

        if ($amount <= 0) {
            return null;
        }

        $invoiceId = $this->intFrom($payment, ['sales_invoice_id', 'invoice_id', 'sale_invoice_id']);
        $customerId = $this->intFrom($payment, ['customer_id', 'buyer_id', 'client_id']);

        if (! $customerId && $invoiceId && Schema::hasTable('sales_invoices')) {
            $customerId = (int) (DB::table('sales_invoices')->where('id', $invoiceId)->value('customer_id') ?: 0);
        }

        $reference = $this->referenceFrom($payment, ['receipt_number', 'payment_reference', 'reference', 'mpesa_receipt', 'transaction_code'], 'PAY-' . $id);

        return $this->autoPosting->postCustomerReceipt([
            'amount' => $amount,
            'payment_method' => $this->paymentMethodFrom($payment),
            'transaction_date' => $this->dateFrom($payment, ['payment_date', 'paid_at', 'transaction_date', 'date', 'created_at']),
            'reference' => $reference,
            'narration' => 'Customer receipt posted to accounting: ' . $reference,
            'source_type' => 'sales_payment',
            'source_id' => $id,
            'customer_type' => $customerId ? 'customer' : null,
            'customer_id' => $customerId,
        ]);
    }

    public function postPurchaseOrder(Model $purchaseOrder): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($purchaseOrder)) {
            return null;
        }

        $id = $this->id($purchaseOrder);

        if (! $id || $this->alreadyPosted('purchase_order', $id)) {
            return null;
        }

        $status = strtolower((string) $this->firstValue($purchaseOrder, ['status', 'approval_status', 'receipt_status']));

        if ($status && ! str_contains($status, 'received') && ! str_contains($status, 'complete') && ! str_contains($status, 'approved')) {
            return null;
        }

        $amount = $this->amountFrom($purchaseOrder, ['grand_total', 'total_amount', 'order_total', 'net_total', 'total', 'amount']);

        if ($amount <= 0) {
            $amount = $this->sumChildAmount('purchase_order_items', ['purchase_order_id', 'order_id'], $id, [
                'line_total', 'total_amount', 'total', 'subtotal', 'amount', 'price_total',
            ]);
        }

        if ($amount <= 0) {
            return null;
        }

        $supplierId = $this->intFrom($purchaseOrder, ['supplier_id', 'vendor_id']);
        $reference = $this->referenceFrom($purchaseOrder, ['po_number', 'order_number', 'purchase_order_number', 'reference'], 'PO-' . $id);

        return $this->autoPosting->postPurchase([
            'amount' => $amount,
            'vat_amount' => $this->amountFrom($purchaseOrder, ['vat_amount', 'tax_amount', 'vat', 'tax']),
            'is_credit' => true,
            'debit_mapping_key' => 'inventory_asset',
            'transaction_date' => $this->dateFrom($purchaseOrder, ['order_date', 'purchase_date', 'transaction_date', 'date', 'created_at']),
            'reference' => $reference,
            'narration' => 'Purchase order posted to accounting: ' . $reference,
            'source_type' => 'purchase_order',
            'source_id' => $id,
            'supplier_type' => $supplierId ? 'supplier' : null,
            'supplier_id' => $supplierId,
            'description' => 'Recognize supplier bill / inventory purchase',
        ]);
    }

    public function postPurchaseOrderReceipt(Model $receipt): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($receipt)) {
            return null;
        }

        $id = $this->id($receipt);

        if (! $id || $this->alreadyPosted('purchase_order_receipt', $id)) {
            return null;
        }

        $amount = $this->amountFrom($receipt, ['grand_total', 'total_amount', 'receipt_total', 'net_total', 'total', 'amount']);

        if ($amount <= 0) {
            $amount = $this->sumChildAmount('purchase_order_receipt_items', ['purchase_order_receipt_id', 'receipt_id'], $id, [
                'line_total', 'total_amount', 'total', 'subtotal', 'amount', 'price_total',
            ]);
        }

        if ($amount <= 0) {
            return null;
        }

        $orderId = $this->intFrom($receipt, ['purchase_order_id', 'order_id']);
        $supplierId = $this->intFrom($receipt, ['supplier_id', 'vendor_id']);

        if (! $supplierId && $orderId && Schema::hasTable('purchase_orders')) {
            $supplierId = (int) (DB::table('purchase_orders')->where('id', $orderId)->value('supplier_id') ?: 0);
        }

        $reference = $this->referenceFrom($receipt, ['receipt_number', 'grn_number', 'reference'], 'GRN-' . $id);

        return $this->autoPosting->postPurchase([
            'amount' => $amount,
            'vat_amount' => $this->amountFrom($receipt, ['vat_amount', 'tax_amount', 'vat', 'tax']),
            'is_credit' => true,
            'debit_mapping_key' => 'inventory_asset',
            'transaction_date' => $this->dateFrom($receipt, ['receipt_date', 'received_at', 'transaction_date', 'date', 'created_at']),
            'reference' => $reference,
            'narration' => 'Purchase receipt posted to accounting: ' . $reference,
            'source_type' => 'purchase_order_receipt',
            'source_id' => $id,
            'supplier_type' => $supplierId ? 'supplier' : null,
            'supplier_id' => $supplierId,
            'description' => 'Recognize received inventory / supplier payable',
        ]);
    }

    public function postPurchaseOrderPayment(Model $payment): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($payment)) {
            return null;
        }

        $id = $this->id($payment);

        if (! $id || $this->alreadyPosted('purchase_order_payment', $id)) {
            return null;
        }

        $amount = $this->amountFrom($payment, ['amount', 'paid_amount', 'amount_paid', 'payment_amount']);

        if ($amount <= 0) {
            return null;
        }

        $orderId = $this->intFrom($payment, ['purchase_order_id', 'order_id']);
        $supplierId = $this->intFrom($payment, ['supplier_id', 'vendor_id']);

        if (! $supplierId && $orderId && Schema::hasTable('purchase_orders')) {
            $supplierId = (int) (DB::table('purchase_orders')->where('id', $orderId)->value('supplier_id') ?: 0);
        }

        $reference = $this->referenceFrom($payment, ['payment_reference', 'reference', 'receipt_number', 'mpesa_receipt', 'transaction_code'], 'SUP-PAY-' . $id);

        return $this->autoPosting->postSupplierPayment([
            'amount' => $amount,
            'payment_method' => $this->paymentMethodFrom($payment),
            'transaction_date' => $this->dateFrom($payment, ['payment_date', 'paid_at', 'transaction_date', 'date', 'created_at']),
            'reference' => $reference,
            'narration' => 'Supplier payment posted to accounting: ' . $reference,
            'source_type' => 'purchase_order_payment',
            'source_id' => $id,
            'supplier_type' => $supplierId ? 'supplier' : null,
            'supplier_id' => $supplierId,
        ]);
    }

    public function postProjectExpense(Model $expense): ?AccountingJournalEntry
    {
        if ($this->shouldSkipByStatus($expense)) {
            return null;
        }

        $id = $this->id($expense);

        if (! $id || $this->alreadyPosted('project_expense', $id)) {
            return null;
        }

        $amount = $this->amountFrom($expense, ['amount', 'total_amount', 'cost', 'expense_amount', 'paid_amount']);

        if ($amount <= 0) {
            return null;
        }

        $reference = $this->referenceFrom($expense, ['expense_number', 'reference', 'voucher_number'], 'EXP-' . $id);

        return $this->autoPosting->postPurchase([
            'amount' => $amount,
            'vat_amount' => $this->amountFrom($expense, ['vat_amount', 'tax_amount', 'vat', 'tax']),
            'is_credit' => false,
            'debit_mapping_key' => 'project_expense',
            'payment_method' => $this->paymentMethodFrom($expense),
            'transaction_date' => $this->dateFrom($expense, ['expense_date', 'transaction_date', 'date', 'created_at']),
            'reference' => $reference,
            'narration' => 'Project/farm expense posted to accounting: ' . $reference,
            'source_type' => 'project_expense',
            'source_id' => $id,
            'project_fund_id' => $this->intFrom($expense, ['project_fund_id', 'fund_id']),
            'cost_center_id' => $this->intFrom($expense, ['cost_center_id', 'department_id']),
            'description' => $this->descriptionFrom($expense, 'Farm/project expense'),
        ]);
    }

    public function postAnimalFeedingCost(Model $feeding): ?AccountingJournalEntry
    {
        $id = $this->id($feeding);

        if (! $id || $this->alreadyPosted('animal_feeding', $id)) {
            return null;
        }

        $amount = $this->amountFrom($feeding, ['total_cost', 'cost', 'amount', 'feed_cost']);

        if ($amount <= 0) {
            $amount = $this->sumChildAmount('animal_feeding_items', ['animal_feeding_id', 'feeding_id'], $id, [
                'total_cost', 'line_total', 'cost', 'amount', 'value',
            ]);
        }

        if ($amount <= 0) {
            return null;
        }

        return $this->postInventoryUsageJournal(
            sourceType: 'animal_feeding',
            sourceId: $id,
            debitMapping: 'feed_cost',
            creditMapping: 'feed_inventory',
            amount: $amount,
            date: $this->dateFrom($feeding, ['feeding_date', 'date', 'created_at']),
            reference: $this->referenceFrom($feeding, ['feeding_number', 'reference'], 'FEED-' . $id),
            narration: 'Animal feeding cost posted to accounting'
        );
    }

    public function postAnimalHealthCost(Model $health): ?AccountingJournalEntry
    {
        $id = $this->id($health);

        if (! $id || $this->alreadyPosted('animal_health_record', $id)) {
            return null;
        }

        $amount = $this->amountFrom($health, ['total_cost', 'cost', 'amount', 'drug_cost', 'treatment_cost']);

        if ($amount <= 0) {
            return null;
        }

        return $this->postInventoryUsageJournal(
            sourceType: 'animal_health_record',
            sourceId: $id,
            debitMapping: 'veterinary_cost',
            creditMapping: 'veterinary_inventory',
            amount: $amount,
            date: $this->dateFrom($health, ['treatment_date', 'date_given', 'recorded_at', 'date', 'created_at']),
            reference: $this->referenceFrom($health, ['reference', 'record_number'], 'VET-' . $id),
            narration: 'Animal health/veterinary cost posted to accounting'
        );
    }

    private function postInventoryUsageJournal(string $sourceType, int $sourceId, string $debitMapping, string $creditMapping, float $amount, mixed $date, string $reference, string $narration): AccountingJournalEntry
    {
        $debitAccount = $this->accounting->accountFromMapping($debitMapping);
        $creditAccount = $this->accounting->accountFromMapping($creditMapping);

        return $this->accounting->createJournalEntry([
            'transaction_date' => $date,
            'reference' => $reference,
            'narration' => $narration . ': ' . $reference,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'metadata' => ['auto_posted' => true],
        ], [
            [
                'account_id' => $debitAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Recognize farm consumption cost',
            ],
            [
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Reduce inventory value',
            ],
        ], true);
    }

    public function alreadyPosted(string $sourceType, int|string|null $sourceId): bool
    {
        if (! $sourceId || ! Schema::hasTable('accounting_journal_entries')) {
            return false;
        }

        return DB::table('accounting_journal_entries')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereIn('status', ['draft', 'posted'])
            ->exists();
    }

    private function guessSalesIncomeMapping(Model $invoice, int $invoiceId): string
    {
        $text = strtolower(implode(' ', array_filter([
            (string) $this->firstValue($invoice, ['item_type', 'sale_type', 'category', 'description', 'notes']),
            $this->childText('sales_invoice_items', ['sales_invoice_id', 'invoice_id'], $invoiceId, ['item_type', 'product_type', 'description', 'name']),
        ])));

        return match (true) {
            str_contains($text, 'crop'), str_contains($text, 'maize'), str_contains($text, 'avocado') => 'crop_sales_income',
            str_contains($text, 'milk') => 'milk_sales_income',
            str_contains($text, 'egg') => 'egg_sales_income',
            str_contains($text, 'nursery'), str_contains($text, 'seedling') => 'nursery_sales_income',
            default => 'livestock_sales_income',
        };
    }

    private function shouldSkipByStatus(Model $model): bool
    {
        $status = strtolower(trim((string) $this->firstValue($model, ['status', 'approval_status', 'payment_status'])));

        if ($status === '') {
            return false;
        }

        foreach (['draft', 'cancelled', 'canceled', 'void', 'reversed', 'deleted', 'rejected'] as $badStatus) {
            if (str_contains($status, $badStatus)) {
                return true;
            }
        }

        return false;
    }

    private function paymentMethodFrom(Model $model): string
    {
        $method = strtolower(trim((string) $this->firstValue($model, ['payment_method', 'method', 'mode', 'payment_mode'])));

        return match (true) {
            str_contains($method, 'cash') => 'cash',
            str_contains($method, 'mpesa'), str_contains($method, 'm-pesa'), str_contains($method, 'm pesa') => 'mpesa',
            str_contains($method, 'petty') => 'petty_cash',
            default => 'bank',
        };
    }

    private function amountFrom(Model $model, array $fields): float
    {
        foreach ($fields as $field) {
            $value = $this->firstValue($model, [$field]);

            if (is_numeric($value)) {
                return round((float) $value, 2);
            }
        }

        return 0.0;
    }

    private function intFrom(Model $model, array $fields): ?int
    {
        foreach ($fields as $field) {
            $value = $this->firstValue($model, [$field]);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function firstValue(Model $model, array $fields): mixed
    {
        $attributes = $model->getAttributes();

        foreach ($fields as $field) {
            if (array_key_exists($field, $attributes)) {
                return $attributes[$field];
            }
        }

        return null;
    }

    private function id(Model $model): ?int
    {
        $id = $model->getKey() ?: $this->firstValue($model, ['id']);

        return is_numeric($id) ? (int) $id : null;
    }

    private function referenceFrom(Model $model, array $fields, string $fallback): string
    {
        foreach ($fields as $field) {
            $value = $this->firstValue($model, [$field]);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return $fallback;
    }

    private function descriptionFrom(Model $model, string $fallback): string
    {
        return $this->referenceFrom($model, ['description', 'notes', 'narration', 'purpose'], $fallback);
    }

    private function dateFrom(Model $model, array $fields): mixed
    {
        foreach ($fields as $field) {
            $value = $this->firstValue($model, [$field]);

            if ($value) {
                return $value;
            }
        }

        return now();
    }

    private function sumChildAmount(string $table, array $foreignKeys, int $parentId, array $amountFields): float
    {
        if (! Schema::hasTable($table)) {
            return 0.0;
        }

        $foreignKey = collect($foreignKeys)->first(fn (string $key) => Schema::hasColumn($table, $key));
        $amountField = collect($amountFields)->first(fn (string $field) => Schema::hasColumn($table, $field));

        if (! $foreignKey || ! $amountField) {
            return 0.0;
        }

        return round((float) DB::table($table)->where($foreignKey, $parentId)->sum($amountField), 2);
    }

    private function childText(string $table, array $foreignKeys, int $parentId, array $textFields): string
    {
        if (! Schema::hasTable($table)) {
            return '';
        }

        $foreignKey = collect($foreignKeys)->first(fn (string $key) => Schema::hasColumn($table, $key));
        $fields = collect($textFields)->filter(fn (string $field) => Schema::hasColumn($table, $field))->values()->all();

        if (! $foreignKey || empty($fields)) {
            return '';
        }

        return DB::table($table)
            ->where($foreignKey, $parentId)
            ->limit(10)
            ->get($fields)
            ->map(fn ($row) => implode(' ', array_map('strval', (array) $row)))
            ->implode(' ');
    }
}
