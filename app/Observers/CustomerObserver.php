<?php

namespace App\Observers;

use App\Models\Sales\Customer;

class CustomerObserver
{
    public function creating(Customer $customer): void
    {
        if (blank($customer->customer_number)) {
            $latestId = Customer::withTrashed()->max('id') ?? 0;

            $customer->customer_number = 'LLK-CUS-' . str_pad($latestId + 1, 5, '0', STR_PAD_LEFT);
        }

        if (auth()->check()) {
            $customer->created_by = auth()->id();
            $customer->updated_by = auth()->id();
        }
    }

    public function updating(Customer $customer): void
    {
        if (auth()->check()) {
            $customer->updated_by = auth()->id();
        }
    }
}
