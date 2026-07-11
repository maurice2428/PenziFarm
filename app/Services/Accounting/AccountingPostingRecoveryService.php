<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingPostingFailure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountingPostingRecoveryService
{
    public function retry(
        AccountingPostingFailure $failure
    ): void {
        $model = $this->resolveModel($failure);

        try {
            $journal = app(AccountingIntegrationPostingService::class)
                ->postModel($model, 'failure-retry');

            $canonicalType = match ($failure->source_type) {
                'sales_invoices' => 'sales_invoice',
                'sales_payments' => 'sales_payment',
                'purchase_order_receipts' => 'purchase_order_receipt',
                'purchase_order_payments' => 'purchase_order_payment',
                'stock_movements' => 'stock_movement',
                'payrolls' => 'payroll',
                'payroll_payments' => 'payroll_payment',
                'statutory_remittances' => 'statutory_remittance',
                'operating_expenses' => 'operating_expense',
                'operating_expense_payments' => 'operating_expense_payment',
                default => $failure->source_type,
            };

            $posted = $journal
                || \App\Models\Accounting\AccountingSourcePosting::query()
                    ->where('source_type', $canonicalType)
                    ->where('source_id', $failure->source_id)
                    ->whereIn('status', ['draft', 'posted', 'reversed'])
                    ->exists();

            if (! $posted) {
                throw ValidationException::withMessages([
                    'failure' =>
                        'The retry did not create a journal. The source may '
                        . 'still be incomplete, ineligible, or missing a '
                        . 'required account mapping.',
                ]);
            }

            $failure->forceFill([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'attempts' => (int) $failure->attempts + 1,
                'last_attempted_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            $failure->forceFill([
                'status' => 'pending',
                'attempts' => (int) $failure->attempts + 1,
                'last_attempted_at' => now(),
                'exception_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    public function ignore(
        AccountingPostingFailure $failure,
        string $reason
    ): void {
        $failure->forceFill([
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'metadata' => array_merge(
                $failure->metadata ?? [],
                ['ignored_reason' => trim($reason)]
            ),
        ])->save();
    }

    private function resolveModel(
        AccountingPostingFailure $failure
    ): Model {
        $modelClass = data_get(
            $failure->metadata,
            'model_class'
        );

        $map = [
            'sales_invoice' => 'App\\Models\\Sales\\SalesInvoice',
            'sales_invoices' => 'App\\Models\\Sales\\SalesInvoice',
            'sales_payment' => 'App\\Models\\Sales\\SalesPayment',
            'sales_payments' => 'App\\Models\\Sales\\SalesPayment',
            'purchase_order_receipt' => 'App\\Models\\PurchaseOrderReceipt',
            'purchase_order_receipts' => 'App\\Models\\PurchaseOrderReceipt',
            'purchase_order_payment' => 'App\\Models\\PurchaseOrderPayment',
            'purchase_order_payments' => 'App\\Models\\PurchaseOrderPayment',
            'stock_movement' => 'App\\Models\\StockMovement',
            'stock_movements' => 'App\\Models\\StockMovement',
            'payroll' => 'App\\Models\\HR\\Payroll',
            'payrolls' => 'App\\Models\\HR\\Payroll',
            'animal_feeding' => 'App\\Models\\AnimalFeeding',
            'animal_feedings' => 'App\\Models\\AnimalFeeding',
        ];

        $modelClass = $modelClass
            ?: ($map[$failure->source_type] ?? null);

        if (! $modelClass || ! class_exists($modelClass)) {
            throw ValidationException::withMessages([
                'failure' =>
                    'The source model could not be resolved. '
                    . 'Inspect the failure metadata and post manually.',
            ]);
        }

        /** @var Model|null $model */
        $model = $modelClass::query()->find(
            $failure->source_id
        );

        if (! $model) {
            throw ValidationException::withMessages([
                'failure' => 'The source record no longer exists.',
            ]);
        }

        return $model;
    }
}
