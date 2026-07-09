<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    protected $fillable = [
        'tag_number',
        'tag_sequence',
        'species',
        'breed_id',
        'sex',
        'date_of_birth',
        'date_of_birth_is_estimated',
        'source',
        'bought_on',
        'bought_from',
        'seller_phone',
        'seller_email',
        'seller_address',
        'purchase_price',
        'purchase_notes',
        'source_reference_type',
        'source_reference_id',
        'status',
        'is_archived',
        'purpose',
        'is_breeder',
        'sale_ready',
        'valuation_price',
        'current_location_id',
        'sire_id',
        'dam_id',
        'notes',
        'created_by',
        'updated_by',
        'date_died',
        'cause_of_death',
        'death_comments',
        'date_culled',
        'culling_reason',
        'culling_comments',
        'purity_breed_id',
        'breed_purity_percent',
        'purity_override_percent',
        'purity_status',
        'is_foundation_animal',
        'purity_verified_at',
        'purity_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'bought_on' => 'date',
        'date_died' => 'date',
        'date_culled' => 'date',
        'date_of_birth_is_estimated' => 'boolean',
        'is_breeder' => 'boolean',
        'sale_ready' => 'boolean',
        'is_archived' => 'boolean',
        'valuation_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'breed_purity_percent' => 'decimal:4',
        'purity_override_percent' => 'decimal:4',
        'is_foundation_animal' => 'boolean',
        'purity_verified_at' => 'date',
    ];

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function purityBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'purity_breed_id');
    }

    public function offspringAsSire(): HasMany
    {
        return $this->hasMany(self::class, 'sire_id');
    }

    public function offspringAsDam(): HasMany
    {
        return $this->hasMany(self::class, 'dam_id');
    }

    public function healthAdministrations(): BelongsToMany
    {
        return $this->belongsToMany(
            HealthAdministration::class,
            'health_administration_animals',
            'animal_id',
            'health_administration_id'
        )->withTimestamps();
    }

    public function clinicalCases(): HasMany
    {
        return $this->hasMany(AnimalClinicalCase::class);
    }

    public function treatmentRecords(): HasMany
    {
        return $this->hasMany(AnimalTreatmentRecord::class);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(AnimalLabRequest::class);
    }

    use LogsActivity;

    public function getAuditModule(): string
    {
        return 'Livestock';
    }

    public function getAuditLabel(): string
    {
        return $this->tag_number ?? ('Animal #' . $this->id);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnimalEvent::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(AnimalWeight::class);
    }

    public function latestWeight(): HasOne
    {
        return $this
            ->hasOne(AnimalWeight::class, 'animal_id')
            ->whereNull('deleted_at')
            ->latestOfMany('recorded_at');
    }

    public function breedingRecordsAsFemale(): HasMany
    {
        return $this->hasMany(BreedingRecord::class, 'female_animal_id');
    }

    public function breedingRecordsAsMale(): HasMany
    {
        return $this->hasMany(BreedingRecord::class, 'male_animal_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->tag_number . ' - ' . ($this->breed?->breed_name ?? 'Unknown Breed');
    }

    public function healthRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AnimalHealthRecord::class);
    }

    public function activeClinicalCases(): HasMany
    {
        return $this
            ->hasMany(AnimalClinicalCase::class)
            ->whereNotIn('status', [
                'Resolved',
                'Closed',
            ]);
    }

    public function latestActiveClinicalCase(): HasOne
    {
        return $this
            ->hasOne(AnimalClinicalCase::class)
            ->whereNotIn('status', [
                'Resolved',
                'Closed',
            ])
            ->latestOfMany('case_date');
    }

    public function transferItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AnimalTransferItem::class);
    }

    public function groupMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AnimalGroupMember::class);
    }


    public function sire()
    {
        return $this->belongsTo(self::class, 'sire_id');
    }

    public function dam()
    {
        return $this->belongsTo(self::class, 'dam_id');
    }
}
