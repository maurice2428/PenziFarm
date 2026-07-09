<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AnimalClinicalCase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'case_number',
        'animal_id',
        'case_date',
        'status',
        'severity',
        'clinical_signs',
        'diagnosis',
        'treatment_plan',
        'length_of_illness',
        'temperature_c',
        'animal_source',
        'attending_officer',
        'remarks',
        'resolved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'case_date' => 'datetime',
        'resolved_at' => 'datetime',
        'temperature_c' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            'Open' => 'Open',
            'Under Treatment' => 'Under Treatment',
            'Resolved' => 'Resolved',
            'Referred' => 'Referred',
            'Closed' => 'Closed',
        ];
    }

    public static function severities(): array
    {
        return [
            'Low' => 'Low',
            'Moderate' => 'Moderate',
            'High' => 'High',
            'Critical' => 'Critical',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            if (blank($record->case_number)) {
                $record->case_number = self::nextCaseNumber();
            }

            if (auth()->check()) {
                $record->created_by ??= auth()->id();
                $record->updated_by ??= auth()->id();
            }
        });

        static::updating(function (self $record): void {
            if (auth()->check()) {
                $record->updated_by = auth()->id();
            }
        });
    }

    private static function nextCaseNumber(): string
    {
        do {
            $number = 'PENZI-CASE-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
        } while (self::withTrashed()->where('case_number', $number)->exists());

        return $number;
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(AnimalTreatmentRecord::class, 'clinical_case_id');
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(AnimalLabRequest::class, 'clinical_case_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
