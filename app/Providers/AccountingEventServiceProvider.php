<?php

namespace App\Providers;

use App\Observers\AccountingSourceObserver;
use Illuminate\Support\ServiceProvider;

class AccountingEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $models = [
            'App\\Models\\Sales\\SalesInvoice',
            'App\\Models\\Sales\\SalesPayment',
            'App\\Models\\PurchaseOrderReceipt',
            'App\\Models\\PurchaseOrderPayment',
            'App\\Models\\StockMovement',
            'App\\Models\\HR\\Payroll',
            'App\\Models\\ProjectExpense',
            'App\\Models\\Projects\\ProjectExpense',
            'App\\Models\\AnimalFeeding',
            'App\\Models\\AnimalHealthRecord',
        ];

        foreach (array_unique($models) as $model) {
            if (class_exists($model)) {
                $model::observe(AccountingSourceObserver::class);
            }
        }
    }
}
