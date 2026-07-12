<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'movement_type',
        'effective_date',
        'from_department_id',
        'to_department_id',
        'from_job_title_id',
        'to_job_title_id',
        'from_basic_salary',
        'to_basic_salary',
        'previous_status',
        'new_status',
        'reason',
        'notes',
        'supporting_document_path',
        'approval_status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'from_basic_salary' => 'decimal:2',
        'to_basic_salary' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'from_job_title_id');
    }

    public function toJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'to_job_title_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
