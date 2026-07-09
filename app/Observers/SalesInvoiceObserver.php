<?php

namespace App\Observers;

use App\Models\Animal;
use App\Models\Sales\SalesInvoice;

class SalesInvoiceObserver
{
    public function deleted(SalesInvoice $invoice): void
    {
        $invoice->items()
            ->whereNotNull('animal_id')
            ->pluck('animal_id')
            ->each(function ($animalId) {
                Animal::whereKey($animalId)->update([
                    'status' => 'Active',
                ]);
            });
    }
}
