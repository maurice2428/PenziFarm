<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id', 'employee_id', 'pay_period_start', 'pay_period_end', 'gross_pay',
        'taxable_pay', 'paye', 'statutory_deductions', 'other_deductions', 'net_pay',
        'pdf_path', 'email_sent', 'emailed_at'
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'gross_pay' => 'decimal:2',
        'taxable_pay' => 'decimal:2',
        'paye' => 'decimal:2',
        'statutory_deductions' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'email_sent' => 'boolean',
        'emailed_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
