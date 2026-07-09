<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalTreatmentRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinical_case_id',
        'animal_id',
        'given_at',
        'medicine_name',
        'dosage',
        'method',
        'frequency',
        'duration',
        'quantity_used',
        'status',
        'administered_by',
        'follow_up_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'given_at' => 'datetime',
        'follow_up_date' => 'date',
        'quantity_used' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            'Planned' => 'Planned',
            'Completed' => 'Completed',
            'Stopped' => 'Stopped',
            'Follow-up Required' => 'Follow-up Required',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
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

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(AnimalClinicalCase::class, 'clinical_case_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
