<?php

namespace App\Services\Procurement;

use App\Models\Accounting\AccountingJournalEntry;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderPayment;
use App\Models\PurchaseOrderReceipt;
use App\Services\Accounting\AccountingService;
use App\Services\Inventory\InventoryLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProcurementLifecycleService
{
    public function recordPayment(
        PurchaseOrder $purchaseOrder,
        array $data
    ): PurchaseOrderPayment {
        return DB::transaction(function () use (
            $purchaseOrder,
            $data
        ): PurchaseOrderPayment {
            $order = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->getKey());

            $order->syncPaymentTotals();
            $order->refresh();

            $amount = round(
                (float) ($data['amount'] ?? 0),
                2
            );

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment amount must be greater than zero.',
                ]);
            }

            if ($amount > (float) $order->balance_due + 0.009) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment exceeds the current supplier invoice '
                        . 'balance of KES '
                        . number_format(
                            (float) $order->balance_due,
                            2
                        )
                        . '.',
                ]);
            }

            $paidAt = $data['paid_at']
                ?? $data['payment_date']
                ?? now('Africa/Nairobi');

            $payment = PurchaseOrderPayment::query()->create([
                'purchase_order_id' => $order->getKey(),
                'payment_date' =>
                    \Illuminate\Support\Carbon::parse(
                        $paidAt,
                        'Africa/Nairobi'
                    )->toDateString(),
                'paid_at' => $paidAt,
                'amount' => $amount,
                'payment_method' =>
                    $data['payment_method'] ?? 'cash',
                'status' =>
                    $data['status'] ?? 'successful',
                'mpesa_reference' =>
                    $data['mpesa_reference']
                    ?? $data['mpesa_receipt_number']
                    ?? null,
                'mpesa_phone' =>
                    $data['mpesa_phone'] ?? null,
                'mpesa_receipt_number' =>
                    $data['mpesa_receipt_number']
                    ?? $data['mpesa_reference']
                    ?? null,
                'mpesa_merchant_request_id' =>
                    $data['mpesa_merchant_request_id']
                    ?? null,
                'mpesa_checkout_request_id' =>
                    $data['mpesa_checkout_request_id']
                    ?? null,
                'mpesa_result_code' =>
                    $data['mpesa_result_code'] ?? null,
                'mpesa_result_description' =>
                    $data['mpesa_result_description']
                    ?? null,
                'mpesa_callback_payload' =>
                    $data['mpesa_callback_payload'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_reference' =>
                    $data['bank_reference'] ?? null,
                'cheque_number' =>
                    $data['cheque_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $order->refresh();
            $order->syncPaymentTotals();

            return $payment->refresh();
        });
    }

    public function reversePayment(
        PurchaseOrderPayment $payment,
        string $reason
    ): PurchaseOrderPayment {
        return DB::transaction(function () use (
            $payment,
            $reason
        ): PurchaseOrderPayment {
            $locked = PurchaseOrderPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if (! $locked->can_be_reversed) {
                throw ValidationException::withMessages([
                    'reversal_reason' =>
                        'Only a successful, unreversed payment '
                        . 'can be reversed.',
                ]);
            }

            $this->reverseAccountingSource(
                'purchase_order_payment',
                $locked->getKey(),
                $reason
            );

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_at' => now('Africa/Nairobi'),
                'reversed_by' => auth()->id(),
                'reversal_reason' => $reason,
            ])->save();

            $locked->purchaseOrder?->syncPaymentTotals();

            return $locked->refresh();
        });
    }

    public function reverseReceipt(
        PurchaseOrderReceipt $receipt,
        string $reason
    ): PurchaseOrderReceipt {
        return DB::transaction(function () use (
            $receipt,
            $reason
        ): PurchaseOrderReceipt {
            $lockedReceipt = PurchaseOrderReceipt::query()
                ->lockForUpdate()
                ->with([
                    'items.inventoryItem',
                    'purchaseOrder.items',
                ])
                ->findOrFail($receipt->getKey());

            if (! $lockedReceipt->can_be_reversed) {
                throw ValidationException::withMessages([
                    'reversal_reason' =>
                        'This goods received note has already been '
                        . 'reversed or is not in a reversible status.',
                ]);
            }

            /*
             * Capture the source order before changing the GRN. The
             * relationship includes archived orders, while the direct
             * fallback also supports older records whose relation was
             * not eager-loaded correctly.
             */
            $purchaseOrder = $lockedReceipt->purchaseOrder
                ?: PurchaseOrder::query()
                    ->withTrashed()
                    ->find(
                        $lockedReceipt->purchase_order_id
                    );

            foreach ($lockedReceipt->items as $line) {
                $quantity = (float) $line->accepted_quantity;

                if ($quantity <= 0) {
                    continue;
                }

                $item = InventoryItem::query()
                    ->lockForUpdate()
                    ->findOrFail($line->inventory_item_id);

                app(InventoryLedgerService::class)
                    ->recordOut(
                        item: $item,
                        quantity: $quantity,
                        unitCost:
                            (float) $line->unit_cost,
                        type:
                            'purchase_receipt_reversal',
                        movementDate:
                            now('Africa/Nairobi')
                                ->toDateString(),
                        referenceable: $lockedReceipt,
                        purchaseOrderId:
                            $lockedReceipt
                                ->purchase_order_id,
                        batchNumber:
                            $line->batch_number,
                        expiryDate:
                            $line->expiry_date
                                ?->format('Y-m-d'),
                        notes:
                            'Reversal of '
                            . $lockedReceipt->receipt_no
                            . ': '
                            . $reason,
                        source:
                            'procurement_reversal',
                    );
            }

            $this->reverseAccountingSource(
                'purchase_order_receipt',
                $lockedReceipt->getKey(),
                $reason
            );

            $lockedReceipt->forceFill([
                'status' => 'reversed',
                'reversed_at' =>
                    now('Africa/Nairobi'),
                'reversed_by' => auth()->id(),
                'reversal_reason' => $reason,
            ])->save();

            $receivingService = app(
                PurchaseReceivingService::class
            );

            /*
             * Recalculate line-level received quantities after this GRN
             * becomes reversed. receiptItems() automatically excludes
             * reversed and cancelled GRNs.
             */
            foreach ($lockedReceipt->items as $line) {
                $purchaseOrderItem = PurchaseOrderItem::query()
                    ->withTrashed()
                    ->find(
                        $line->purchase_order_item_id
                    );

                if ($purchaseOrderItem) {
                    $receivingService
                        ->syncPurchaseOrderItem(
                            $purchaseOrderItem
                        );
                }
            }

            $receivingService->synchronizeOrder(
                $purchaseOrder
            );

            return $lockedReceipt->refresh();
        });
    }

    public function cancelPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        string $reason
    ): PurchaseOrder {
        return DB::transaction(function () use (
            $purchaseOrder,
            $reason
        ): PurchaseOrder {
            $order = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->getKey());

            if (! $order->canBeCancelledSafely()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' =>
                        'Reverse successful payments and goods '
                        . 'received notes before cancelling this '
                        . 'purchase order.',
                ]);
            }

            $this->reverseAccountingSource(
                'purchase_order',
                $order->getKey(),
                $reason
            );

            $order->forceFill([
                'status' => 'cancelled',
                'notes' => trim(
                    (string) $order->notes
                    . PHP_EOL
                    . '[Cancelled '
                    . now('Africa/Nairobi')
                        ->format('d M Y H:i')
                    . '] '
                    . $reason
                ),
            ])->saveQuietly();

            return $order->refresh();
        });
    }

    public function canDeletePayment(
        PurchaseOrderPayment $payment
    ): bool {
        return $payment->can_be_deleted_safely
            && ! $this->hasActiveAccountingSource(
                'purchase_order_payment',
                $payment->getKey()
            );
    }

    public function canDeleteReceipt(
        PurchaseOrderReceipt $receipt
    ): bool {
        return $receipt->can_be_deleted_safely;
    }

    public function canDeletePurchaseOrder(
        PurchaseOrder $purchaseOrder
    ): bool {
        return $purchaseOrder->canBeDeletedSafely()
            && ! $this->hasActiveAccountingSource(
                'purchase_order',
                $purchaseOrder->getKey()
            );
    }

    public function hasActiveAccountingSource(
        string $sourceType,
        int $sourceId
    ): bool {
        if (! Schema::hasTable(
            'accounting_journal_entries'
        )) {
            return false;
        }

        return AccountingJournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereIn('status', ['draft', 'posted'])
            ->exists();
    }

    private function reverseAccountingSource(
        string $sourceType,
        int $sourceId,
        string $reason
    ): void {
        if (! Schema::hasTable(
            'accounting_journal_entries'
        )) {
            return;
        }

        $journals = AccountingJournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereIn('status', ['draft', 'posted'])
            ->orderBy('id')
            ->get();

        foreach ($journals as $journal) {
            if ($journal->status === 'posted') {
                app(AccountingService::class)
                    ->reverseJournalEntry(
                        $journal,
                        $reason
                    );

                continue;
            }

            $journal->forceFill([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' =>
                    now('Africa/Nairobi'),
                'metadata' => array_merge(
                    $journal->metadata ?? [],
                    [
                        'reversal_reason' => $reason,
                        'reversed_from_procurement' =>
                            true,
                    ]
                ),
            ])->save();
        }
    }
}
