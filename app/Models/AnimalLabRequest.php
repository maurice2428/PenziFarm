<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AnimalLabRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_number',
        'animal_id',
        'clinical_case_id',
        'veterinary_clinic_id',
        'clinic_name',
        'status',
        'requested_at',
        'sample_collected_at',
        'dispatched_at',
        'testing_date',
        'resulted_at',
        'specimens',
        'testing_purpose',
        'requested_tests',
        'clinical_signs',
        'length_of_illness',
        'temperature_c',
        'animal_source',
        'attending_officer',
        'notes',
        'results',
        'recommended_medication',
        'lab_report_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'testing_date' => 'datetime',
        'resulted_at' => 'datetime',
        'specimens' => 'array',
        'requested_tests' => 'array',
        'temperature_c' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            'Requested' => 'Requested',
            'Sample Collected' => 'Sample Collected',
            'Dispatched' => 'Dispatched',
            'In Progress' => 'In Progress',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            if (blank($record->request_number)) {
                $record->request_number = self::nextRequestNumber();
            }

            if (auth()->check()) {
                $record->created_by ??= auth()->id();
                $record->updated_by ??= auth()->id();
            }
        });

        static::saving(function (self $record): void {
            if (filled($record->veterinary_clinic_id)) {
                $clinic = VeterinaryClinic::find(
                    $record->veterinary_clinic_id
                );

                if ($clinic) {
                    $record->clinic_name = $clinic->name;
                }
            }

            if (auth()->check()) {
                $record->updated_by = auth()->id();
            }
        });
    }

    private static function nextRequestNumber(): string
    {
        do {
            $number = 'PENZI-LAB-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
        } while (
            self::withTrashed()
                ->where('request_number', $number)
                ->exists()
        );

        return $number;
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(
            AnimalClinicalCase::class,
            'clinical_case_id'
        );
    }

    public function veterinaryClinic(): BelongsTo
    {
        return $this->belongsTo(
            VeterinaryClinic::class,
            'veterinary_clinic_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getSpecimensTextAttribute(): string
    {
        return collect($this->specimens ?? [])
            ->filter()
            ->implode(', ');
    }

    public function getRequestedTestsTextAttribute(): string
    {
        return collect($this->requested_tests ?? [])
            ->filter()
            ->implode(', ');
    }

    public function getClinicDisplayNameAttribute(): string
    {
        return $this->veterinaryClinic?->display_name
            ?? $this->clinic_name
            ?? 'Not recorded';
    }
}
