<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BreedingRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'breeding_batch_id',
        'female_animal_id',
        'male_animal_id',
        'female_breed_id',
        'male_breed_id',
        'species',
        'breeding_type',
        'is_cross_breed',
        'mating_date',
        'gestation_days',
        'expected_due_date',
        'inbreeding_status',
        'relationship_notes',
        'pregnancy_status',
        'pregnancy_checked_at',
        'delivery_date',
        'birth_outcome',
        'birth_assistance',
        'offspring_count',
        'live_birth_count',
        'stillborn_count',
        'neonatal_death_count',
        'weaned_count',
        'retained_breeding_count',
        'delivery_notes',
        'mothering_score',
        'milk_score',
        'temperament_score',
        'offspring_vigour_score',
        'maternal_notes',
        'evaluation_completed_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_cross_breed' => 'boolean',
        'mating_date' => 'date',
        'expected_due_date' => 'date',
        'pregnancy_checked_at' => 'date',
        'delivery_date' => 'date',
        'evaluation_completed_at' => 'datetime',
        'gestation_days' => 'integer',
        'offspring_count' => 'integer',
        'live_birth_count' => 'integer',
        'stillborn_count' => 'integer',
        'neonatal_death_count' => 'integer',
        'weaned_count' => 'integer',
        'retained_breeding_count' => 'integer',
        'mothering_score' => 'decimal:2',
        'milk_score' => 'decimal:2',
        'temperament_score' => 'decimal:2',
        'offspring_vigour_score' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (BreedingRecord $record): void {
            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }

            if (blank($record->pregnancy_status)) {
                $record->pregnancy_status = 'pending';
            }

            if (blank($record->inbreeding_status)) {
                $record->inbreeding_status = 'clear';
            }
        });

        static::saving(function (BreedingRecord $record): void {
            if (
                auth()->check()
                && Schema::hasColumn('breeding_records', 'updated_by')
            ) {
                $record->updated_by = auth()->id();
            }

            if (
                $record->pregnancy_status === 'delivered'
                && $record->isDirty(['pregnancy_status', 'delivery_date'])
            ) {
                $record->assertDeliveryDateMeetsGestation();
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BreedingBatch::class, 'breeding_batch_id');
    }

    public function female(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'female_animal_id');
    }

    public function male(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'male_animal_id');
    }

    public function femaleBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'female_breed_id');
    }

    public function maleBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'male_breed_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Every generated offspring is linked back through the polymorphic-style
     * source reference fields already present on the animals table.
     */
    public function offspring(): HasMany
    {
        return $this->hasMany(Animal::class, 'source_reference_id')
            ->where('source_reference_type', self::class);
    }

    /**
     * Strict minimum delivery date.
     *
     * Primary rule: mating date + stored gestation_days.
     * Fallback: expected_due_date when a legacy record has no gestation_days.
     */
    public function minimumDeliveryDate(): ?Carbon
    {
        if ($this->mating_date && (int) $this->gestation_days > 0) {
            return Carbon::parse($this->mating_date)
                ->startOfDay()
                ->addDays((int) $this->gestation_days);
        }

        if ($this->expected_due_date) {
            return Carbon::parse($this->expected_due_date)->startOfDay();
        }

        return null;
    }

    public function gestationDaysRemaining(mixed $againstDate = null): ?int
    {
        $minimum = $this->minimumDeliveryDate();

        if (! $minimum) {
            return null;
        }

        $against = $againstDate
            ? Carbon::parse($againstDate)->startOfDay()
            : today('Africa/Nairobi');

        return max(0, $against->diffInDays($minimum, false));
    }

    public function assertDeliveryDateMeetsGestation(mixed $deliveryDate = null): void
    {
        $deliveryValue = $deliveryDate ?: $this->delivery_date;

        if (blank($deliveryValue)) {
            throw ValidationException::withMessages([
                'delivery_date' => 'Delivery date is required before confirming delivery.',
            ]);
        }

        $delivery = Carbon::parse($deliveryValue)->startOfDay();
        $today = today('Africa/Nairobi');

        if ($delivery->greaterThan($today)) {
            throw ValidationException::withMessages([
                'delivery_date' => 'Delivery date cannot be in the future.',
            ]);
        }

        $minimum = $this->minimumDeliveryDate();

        if (! $minimum) {
            throw ValidationException::withMessages([
                'delivery_date' => 'The minimum delivery date cannot be calculated because mating date and gestation days are missing.',
            ]);
        }

        if ($delivery->lessThan($minimum)) {
            $remaining = $delivery->diffInDays($minimum);

            throw ValidationException::withMessages([
                'delivery_date' => sprintf(
                    'Delivery cannot be confirmed before the minimum gestation period is complete. Earliest allowed date: %s. The selected date is %d day(s) too early.',
                    $minimum->format('d M Y'),
                    $remaining,
                ),
            ]);
        }
    }

    public function getPregnancyStatusLabelAttribute(): string
    {
        return str($this->pregnancy_status ?: 'pending')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getBreedingTypeLabelAttribute(): string
    {
        return str($this->breeding_type ?: 'natural')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getInbreedingStatusLabelAttribute(): string
    {
        return str($this->inbreeding_status ?: 'clear')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
