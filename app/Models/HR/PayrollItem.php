<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'basic_salary',
        'allowances_total',
        'overtime_amount',
        'gross_pay',
        'taxable_pay',
        'paye',
        'nssf',
        'employer_nssf',
        'sha',
        'housing_levy',
        'employer_housing_levy',
        'salary_advance_deduction',
        'other_deductions',
        'net_pay',
        'paid_amount',
        'payment_status',
        'days_worked',
        'leave_days',
        'absent_days',
        'status',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'taxable_pay' => 'decimal:2',
        'paye' => 'decimal:2',
        'nssf' => 'decimal:2',
        'employer_nssf' => 'decimal:2',
        'sha' => 'decimal:2',
        'housing_levy' => 'decimal:2',
        'employer_housing_levy' => 'decimal:2',
        'salary_advance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'leave_days' => 'decimal:2',
        'absent_days' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(PayrollPaymentItem::class);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return round(
            max(
                0,
                (float) $this->net_pay
                - (float) $this->paid_amount
            ),
            2
        );
    }
}
