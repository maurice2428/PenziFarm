<?php

namespace App\Listeners\Accounting;

use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Database\Eloquent\Model;

class AutoPostAccountingFromEloquent
{
    private static bool $isPosting = false;

    /**
     * Receives wildcard Eloquent events from AccountingAutoPostingServiceProvider.
     */
    public function handle(string $eventName, array $payload): void
    {
        if (self::$isPosting) {
            return;
        }

        $model = $payload[0] ?? null;

        if (! $model instanceof Model) {
            return;
        }

        if (! in_array($model->getTable(), $this->supportedTables(), true)) {
            return;
        }

        self::$isPosting = true;

        try {
            app(AccountingIntegrationPostingService::class)->postModel($model, $eventName);
        } finally {
            self::$isPosting = false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function supportedTables(): array
    {
        return [
            'sales_invoices',
            'sales_payments',
            'purchase_orders',
            'purchase_order_receipts',
            'purchase_order_payments',
            'project_expenses',
            'animal_feedings',
            'animal_health_records',
        ];
    }
}
