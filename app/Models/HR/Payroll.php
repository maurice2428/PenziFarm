<?php

namespace App\Models\HR;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'month',
        'year',
        'revision',
        'period_start',
        'period_end',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'notes',
        'total_gross',
        'total_paye',
        'total_nssf_employee',
        'total_nssf_employer',
        'total_shif',
        'total_housing_levy_employee',
        'total_housing_levy_employer',
        'total_salary_advance_deductions',
        'total_other_deductions',
        'total_net',
        'total_employer_cost',
        'total_paid',
        'balance_due',
        'payment_status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'revision' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => PayrollStatus::class,
        'total_gross' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'total_nssf_employee' => 'decimal:2',
        'total_nssf_employer' => 'decimal:2',
        'total_shif' => 'decimal:2',
        'total_housing_levy_employee' => 'decimal:2',
        'total_housing_levy_employer' => 'decimal:2',
        'total_salary_advance_deductions' => 'decimal:2',
        'total_other_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_employer_cost' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payroll): void {
            if (
                blank($payroll->month)
                && filled($payroll->period_start)
            ) {
                $periodStart = \Carbon\Carbon::parse(
                    $payroll->period_start
                );

                $payroll->month = (int) $periodStart
                    ->format('m');

                $payroll->year = (int) $periodStart
                    ->format('Y');
            }

            if (
                blank($payroll->revision)
                && filled($payroll->month)
                && filled($payroll->year)
            ) {
                $payroll->revision =
                    ((int) self::query()
                        ->withTrashed()
                        ->where(
                            'month',
                            $payroll->month
                        )
                        ->where(
                            'year',
                            $payroll->year
                        )
                        ->max('revision'))
                    + 1;
            }

            $payroll->revision ??= 1;
        });
    }

    public function getRevisionLabelAttribute(): string
    {
        return 'Run ' . max(
            1,
            (int) ($this->revision ?? 1)
        );
    }

    public function getPeriodLabelAttribute(): string
    {
        return \Carbon\Carbon::create()
            ->month((int) $this->month)
            ->format('F')
            . ' '
            . $this->year
            . ' · '
            . $this->revision_label;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function statutoryRemittances(): HasMany
    {
        return $this->hasMany(StatutoryRemittance::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function statusValue(): string
    {
        return $this->status instanceof PayrollStatus
            ? $this->status->value
            : (string) $this->status;
    }

    public function canReceivePayments(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        return in_array(
            $this->statusValue(),
            ['approved', 'posted'],
            true
        ) && (float) $this->balance_due > 0;
    }

    public function statutoryDue(string $type): float
    {
        return match ($type) {
            'paye' => (float) $this->total_paye,
            'nssf' =>
                (float) $this->total_nssf_employee
                + (float) $this->total_nssf_employer,
            'shif' => (float) $this->total_shif,
            'housing_levy' =>
                (float) $this->total_housing_levy_employee
                + (float) $this->total_housing_levy_employer,
            default => 0.0,
        };
    }
}
