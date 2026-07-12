<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_number',
        'employee_id',
        'incident_date',
        'category',
        'severity',
        'allegation',
        'reported_by',
        'investigation_officer_id',
        'show_cause_issued_at',
        'employee_response',
        'hearing_date',
        'hearing_notes',
        'findings',
        'sanction',
        'decision_date',
        'suspension_start_date',
        'suspension_end_date',
        'appeal_status',
        'appeal_notes',
        'status',
        'attachment_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'show_cause_issued_at' => 'datetime',
        'hearing_date' => 'datetime',
        'decision_date' => 'date',
        'suspension_start_date' => 'date',
        'suspension_end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $case): void {
            if (blank($case->case_number)) {
                $year = now()->format('Y');
                $lastNumber = self::withTrashed()
                    ->where('case_number', 'like', "DISC-{$year}-%")
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(case_number, '-', -1) AS UNSIGNED)) AS max_number")
                    ->value('max_number');

                $case->case_number = 'DISC-'
                    . $year
                    . '-'
                    . str_pad((string) (((int) $lastNumber) + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function investigationOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigation_officer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
