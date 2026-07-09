<?php

namespace App\Models\HR;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;
     //protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected $fillable = [
        'employee_id', 'attendance_date', 'check_in', 'check_out', 'shift_name',
        'hours_worked', 'overtime_hours', 'late_minutes', 'status', 'remarks',
        'adjusted_by', 'adjustment_reason'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'status' => AttendanceStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
