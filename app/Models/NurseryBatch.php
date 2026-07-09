<?php

namespace App\Models;

use App\Services\Crops\CropCalendarService;
use App\Support\CropStageAdvisor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NurseryBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_code',
        'name',
        'crop_catalog_id',
        'farm_field_id',
        'field_partition_id',
        'sowing_date',
        'seed_quantity',
        'seed_unit',
        'expected_germination_from',
        'expected_germination_to',
        'actual_germination_date',
        'expected_transplant_date',
        'initial_seedlings',
        'germinated_seedlings',
        'healthy_seedlings',
        'weak_seedlings',
        'dead_seedlings',
        'transplanted_seedlings',
        'germination_percent',
        'growth_stage',
        'status',
        'total_input_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sowing_date' => 'date',
        'expected_germination_from' => 'date',
        'expected_germination_to' => 'date',
        'actual_germination_date' => 'date',
        'expected_transplant_date' => 'date',
        'seed_quantity' => 'decimal:3',
        'germination_percent' => 'decimal:2',
        'total_input_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (NurseryBatch $batch): void {
            if (blank($batch->batch_code)) {
                $batch->batch_code =
                    'NUR'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            $batch->sowing_date ??= now('Africa/Nairobi')->toDateString();

            if (auth()->check() && blank($batch->created_by)) {
                $batch->created_by = auth()->id();
            }
        });

        static::saving(function (NurseryBatch $batch): void {
            if ((int) $batch->initial_seedlings > 0 && (int) $batch->germinated_seedlings > 0) {
                $batch->germination_percent = round(
                    ((int) $batch->germinated_seedlings / (int) $batch->initial_seedlings) * 100,
                    2
                );
            }

            if (!$batch->cropCatalog || !$batch->sowing_date) {
                return;
            }

            $sowingDate = \Carbon\Carbon::parse($batch->sowing_date)->toDateString();

            $dates = app(\App\Services\Crops\CropCalendarService::class)->datesFor(
                $batch->cropCatalog,
                $sowingDate
            );

            foreach (['expected_germination_from', 'expected_germination_to', 'expected_transplant_date'] as $field) {
                if (blank($batch->{$field}) && filled($dates[$field] ?? null)) {
                    $batch->{$field} = $dates[$field];
                }
            }
        });
    }

    public function cropCatalog()
    {
        return $this->belongsTo(CropCatalog::class);
    }

    public function farmField()
    {
        return $this->belongsTo(FarmField::class);
    }

    public function fieldPartition()
    {
        return $this->belongsTo(FieldPartition::class);
    }

    public function inputApplications()
    {
        return $this->hasMany(CropInputApplication::class);
    }

    public function activities()
    {
        return $this->hasMany(CropActivity::class);
    }

    public function careTasks()
    {
        return $this->hasMany(CropCareTask::class);
    }

    public function syncNurseryTotals(): void
    {
        $this->forceFill([
            'total_input_cost' => $this->inputApplications()->sum('total_cost'),
        ])->saveQuietly();
    }

    public function getAvailableSeedlingsAttribute(): int
    {
        return max(0, (int) $this->healthy_seedlings - (int) $this->transplanted_seedlings);
    }

    public function getCropNameAttribute(): string
    {
        return $this->cropCatalog?->display_name ?? 'N/A';
    }

    /*public function getStageInsightAttribute(): array
    {
        return CropStageAdvisor::analyze(
            $this->cropCatalog?->name ?? 'nursery',
            $this->growth_stage,
            $this->germination_percent,
            'good'
        );
    }*/

    public function getStageImageUrlAttribute(): ?string
    {
        return $this->stage_insight['image_url'] ?? null;
    }

    public function getStageInsightAttribute(): array
    {
        return CropStageAdvisor::analyze(
            cropName: $this->cropCatalog?->name ?? 'nursery',
            stage: $this->growth_stage,
            germinationPercent: $this->germination_percent ? (float) $this->germination_percent : null,
            healthStatus: 'good',
            daysSincePlanting: $this->sowing_date
                ? (int) $this->sowing_date->diffInDays(now('Africa/Nairobi'))
                : null,
            daysToHarvest: null,
        );
    }

    /*public function getStageImageUrlAttribute(): string
    {
        return $this->stage_insight['image_url'] ?? asset('images/crops/stages/placeholder.webp');
    }*/

    public function getStageModelUrlAttribute(): ?string
    {
        return $this->stage_insight['model_url'] ?? null;
    }

    public function getHasStageModelAttribute(): bool
    {
        return filled($this->stage_model_url);
    }

    public function getWateringAdviceAttribute(): string
    {
        return $this->stage_insight['watering'] ?? '-';
    }

    public function getRootStatusAttribute(): string
    {
        return $this->stage_insight['root_status'] ?? '-';
    }

    public function getShootStatusAttribute(): string
    {
        return $this->stage_insight['shoot_status'] ?? '-';
    }

    public function getCareRoutineAdviceAttribute(): string
    {
        return $this->stage_insight['care_routine'] ?? '-';
    }

    public function getNextActionAdviceAttribute(): string
    {
        return $this->stage_insight['next_action'] ?? '-';
    }
}
