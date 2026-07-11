<?php

namespace App\Observers;

use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountingSourceObserver
{
    public function created(Model $model): void
    {
        $this->schedule($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->schedule($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        if (! config('accounting.auto_posting_enabled', true)) {
            return;
        }

        DB::afterCommit(function () use ($model): void {
            app(AccountingIntegrationPostingService::class)
                ->reverseSource(
                    $model,
                    'Source transaction was deleted or archived.'
                );
        });
    }

    private function schedule(Model $model, string $event): void
    {
        if (! config('accounting.auto_posting_enabled', true)) {
            return;
        }

        $modelClass = $model::class;
        $modelId = $model->getKey();
        $table = $model->getTable();
        $status = strtolower(
            $this->stringValue(
                $model->getAttribute('status')
            )
        );
        $reversedAt = $model->getAttribute('reversed_at');
        $reversalReason = $this->stringValue(
            $model->getAttribute('reversal_reason')
            ?: $model->getAttribute('cancellation_reason')
            ?: 'Source transaction was reversed, voided or cancelled.'
        );
        $referenceableType = $model->getAttribute('referenceable_type');
        $referenceableId = $model->getAttribute('referenceable_id');
        $movementType = strtolower(
            $this->stringValue(
                $model->getAttribute('type')
                ?: $model->getAttribute('source')
            )
        );

        DB::afterCommit(function () use (
            $modelClass,
            $modelId,
            $table,
            $status,
            $reversedAt,
            $reversalReason,
            $referenceableType,
            $referenceableId,
            $movementType,
            $event
        ): void {
            if (! class_exists($modelClass)) {
                return;
            }

            $query = $modelClass::query();

            if (method_exists(new $modelClass(), 'trashed')) {
                $query->withTrashed();
            }

            $fresh = $query->find($modelId);

            if (! $fresh) {
                return;
            }

            if (
                filled($reversedAt)
                || in_array(
                    $status,
                    ['reversed', 'void', 'voided', 'cancelled', 'canceled'],
                    true
                )
            ) {
                app(AccountingIntegrationPostingService::class)
                    ->reverseSource($fresh, $reversalReason);

                return;
            }

            /*
             * A purchase receipt is posted only after the complete GRN
             * transaction commits. This avoids recognizing only the first
             * received line when a GRN contains several stock items.
             */
            if (
                $table === 'stock_movements'
                && str_contains($movementType, 'purchase')
                && $referenceableType === 'App\\Models\\PurchaseOrderReceipt'
                && filled($referenceableId)
                && class_exists($referenceableType)
            ) {
                $receipt = $referenceableType::query()
                    ->withTrashed()
                    ->find($referenceableId);

                if ($receipt) {
                    app(AccountingIntegrationPostingService::class)
                        ->postModel($receipt, 'grn-after-commit');
                }

                return;
            }

            app(AccountingIntegrationPostingService::class)
                ->postModel($fresh, $event . '-after-commit');
        });
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
}
