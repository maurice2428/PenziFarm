<?php

namespace App\Models\HR;

use App\Services\HR\EmployeeNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'nationality',
        'date_of_birth',
        'place_of_birth',
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
        'id_document_front_path',
        'id_document_back_path',
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
        'is_tax_resident',
        'nssf_enabled',
        'sha_enabled',
        'housing_levy_enabled',
        'registered_pension_contribution',
        'post_retirement_medical_contribution',
        'mortgage_interest',
        'insurance_relief',
        'insurance_premium',
        'tax_exemption_number',
        'tax_exemption_expiry',
        'tax_supporting_document_path',
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
        'tax_exemption_expiry' => 'date',
        'basic_salary' => 'decimal:2',
        'house_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'tax_enabled' => 'boolean',
        'is_tax_resident' => 'boolean',
        'nssf_enabled' => 'boolean',
        'sha_enabled' => 'boolean',
        'housing_levy_enabled' => 'boolean',
        'registered_pension_contribution' => 'decimal:2',
        'post_retirement_medical_contribution' => 'decimal:2',
        'mortgage_interest' => 'decimal:2',
        'insurance_relief' => 'decimal:2',
        'insurance_premium' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            if (blank($employee->employee_number)) {
                $employee->employee_number = app(EmployeeNumberService::class)->next();
            }
        });

        static::saving(function (self $employee): void {
            $employee->full_name = trim(implode(' ', array_filter([
                $employee->first_name,
                $employee->middle_name,
                $employee->last_name,
            ])));

            // KRA insurance relief is 15% of the qualifying monthly premium,
            // capped at KES 5,000 per month (KES 60,000 per year).
            $employee->insurance_relief = min(
                round(
                    ((float) $employee->insurance_premium)
                    * (float) config('hr.paye.insurance_relief_rate', 0.15),
                    2,
                ),
                (float) config('hr.paye.insurance_relief_monthly_cap', 5000),
            );

            $employee->status = $employee->status ?: 'active';
            $employee->is_active = $employee->status === 'active';
        });
    }

    public static function employeeNumberPrefix(): string
    {
        return app(EmployeeNumberService::class)->prefix();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('hr-employees')
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

    public function movements(): HasMany
    {
        return $this->hasMany(EmployeeMovement::class)->latest('effective_date');
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class)->latest('incident_date');
    }

    public function getTotalAllowanceAttribute(): float
    {
        return (float) $this->house_allowance
            + (float) $this->transport_allowance
            + (float) $this->other_allowance;
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary + $this->total_allowance;
    }

    public function getMaskedIdNumberAttribute(): string
    {
        $value = (string) $this->id_passport_number;

        if ($value === '') {
            return 'Not provided';
        }

        if (mb_strlen($value) <= 4) {
            return str_repeat('•', mb_strlen($value));
        }

        return str_repeat('•', mb_strlen($value) - 4) . mb_substr($value, -4);
    }
}
