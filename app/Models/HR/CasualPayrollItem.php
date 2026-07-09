<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CasualPayrollItem extends Model
{
    use SoftDeletes;

    /*
     * protected $fillable = [
     *     'casual_payroll_id',
     *     'casual_name',
     *     'id_number',
     *     'phone_number',
     *     'designation',
     *     'work_site',
     *     'saturday_amount',
     *     'sunday_amount',
     *     'monday_amount',
     *     'tuesday_amount',
     *     'wednesday_amount',
     *     'thursday_amount',
     *     'friday_amount',
     *     'days_worked',
     *     'total_pay',
     *     'signature',
     *     'remarks',
     * ];
     *
     * protected $casts = [
     *     'saturday_amount' => 'decimal:2',
     *     'sunday_amount' => 'decimal:2',
     *     'monday_amount' => 'decimal:2',
     *     'tuesday_amount' => 'decimal:2',
     *     'wednesday_amount' => 'decimal:2',
     *     'thursday_amount' => 'decimal:2',
     *     'friday_amount' => 'decimal:2',
     *     'days_worked' => 'decimal:2',
     *     'total_pay' => 'decimal:2',
     * ];
     */
    protected $fillable = [
        'casual_payroll_id',
        'casual_name',
        'id_number',
        'phone_number',
        'designation',
        'work_site',
        'daily_rate',
        'saturday_amount',
        'sunday_amount',
        'monday_amount',
        'tuesday_amount',
        'wednesday_amount',
        'thursday_amount',
        'friday_amount',
        'days_worked',
        'total_pay',
        'signature',
        'remarks',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'saturday_amount' => 'decimal:2',
        'sunday_amount' => 'decimal:2',
        'monday_amount' => 'decimal:2',
        'tuesday_amount' => 'decimal:2',
        'wednesday_amount' => 'decimal:2',
        'thursday_amount' => 'decimal:2',
        'friday_amount' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'total_pay' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(CasualPayroll::class, 'casual_payroll_id');
    }

    public function getComputedTotalPayAttribute(): float
    {
        return (float) $this->saturday_amount
            + (float) $this->sunday_amount
            + (float) $this->monday_amount
            + (float) $this->tuesday_amount
            + (float) $this->wednesday_amount
            + (float) $this->thursday_amount
            + (float) $this->friday_amount;
    }
}
