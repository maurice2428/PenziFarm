<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPaymentItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            PayrollPayment::class,
            'payroll_payment_id'
        );
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
