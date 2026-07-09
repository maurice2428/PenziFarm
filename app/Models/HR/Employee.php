<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'id_passport_number',
        'kra_pin',
        'nssf_number',
        'nhif_sha_number',
        'phone',
        'alternate_phone',
        'email',
        'gender',
        'date_of_birth',
        'marital_status',
        'county',
        'address',
        'postal_address',
        'hire_date',
        'department_id',
        'job_title_id',
        'reporting_manager_id',
        'employment_type',
        'work_station',
        'status',
        'avatar_path',
        'contract_start_date',
        'contract_end_date',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'other_allowance',
        'payment_method',
        'bank_name',
        'bank_branch',
        'account_number',
        'mpesa_number',
        'airtel_money_number',
        'tax_enabled',
        'nssf_enabled',
        'sha_enabled',
        'housing_levy_enabled',
        'is_active',
        'exit_date',
        'exit_reason',
        'clearance_status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'exit_date' => 'date',
        'basic_salary' => 'decimal:2',
        'house_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'tax_enabled' => 'boolean',
        'nssf_enabled' => 'boolean',
        'sha_enabled' => 'boolean',
        'housing_levy_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
     * protected static function booted(): void
     * {
     *     static::saving(function (self $employee) {
     *         $employee->full_name = trim(implode(' ', array_filter([
     *             $employee->first_name,
     *             $employee->middle_name,
     *             $employee->last_name,
     *         ])));
     *     });
     * }
     */
    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            if (blank($employee->employee_number)) {
                $prefix = 'LLKSTF';

                $lastNumber = self::withTrashed()
                    ->where('employee_number', 'like', $prefix . '%')
                    ->selectRaw(
                        'MAX(CAST(SUBSTRING(employee_number, ?) AS UNSIGNED)) as max_number',
                        [strlen($prefix) + 1]
                    )
                    ->value('max_number');

                $nextNumber = ((int) $lastNumber) + 1;

                do {
                    $employeeNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                    $nextNumber++;
                } while (
                    self::withTrashed()
                        ->where('employee_number', $employeeNumber)
                        ->exists()
                );

                $employee->employee_number = $employeeNumber;
            }
        });

        static::saving(function (self $employee): void {
            $employee->full_name = trim(implode(' ', array_filter([
                $employee->first_name,
                $employee->middle_name,
                $employee->last_name,
            ])));
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_manager_id');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function salaryAdvances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function getTotalAllowanceAttribute(): float
    {
        return (float) $this->house_allowance
            + (float) $this->transport_allowance
            + (float) $this->other_allowance;
    }
}
