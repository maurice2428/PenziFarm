<?php

namespace App\Observers;

use App\Models\Sales\SalesPayment;

class SalesPaymentObserver
{
    public function saved(SalesPayment $payment): void
    {
        $payment->invoice?->syncPaymentTotals();
        $payment->invoice?->syncAnimalSaleStatus();
    }

    public function deleted(SalesPayment $payment): void
    {
        $payment->invoice?->syncPaymentTotals();
        $payment->invoice?->syncAnimalSaleStatus();
    }

    public function restored(SalesPayment $payment): void
    {
        $payment->invoice?->syncPaymentTotals();
        $payment->invoice?->syncAnimalSaleStatus();
    }
}
