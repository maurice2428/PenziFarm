<?php

namespace App\Models;

use App\Services\Crops\CropCalendarService;
use App\Support\CropStageAdvisor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropSeason extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'season_code',
        'name',
        'crop_catalog_id',
        'farm_field_id',
        'field_partition_id',
        'planting_type',
        'start_date',
        'planting_date',
        'expected_germination_from',
        'expected_germination_to',
        'actual_germination_date',
        'germination_percent',
        'expected_transplant_date',
        'expected_harvest_from',
        'expected_harvest_to',
        'actual_harvest_start',
        'actual_harvest_end',
        'area_planted',
        'area_unit',
        'plant_population',
        'growth_stage',
        'health_status',
        'status',
        'total_input_cost',
        'total_harvest_quantity',
        'harvest_unit',
        'estimated_harvest_value',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'planting_date' => 'date',
        'expected_germination_from' => 'date',
        'expected_germination_to' => 'date',
        'actual_germination_date' => 'date',
        'expected_transplant_date' => 'date',
        'expected_harvest_from' => 'date',
        'expected_harvest_to' => 'date',
        'actual_harvest_start' => 'date',
        'actual_harvest_end' => 'date',
        'germination_percent' => 'decimal:2',
        'area_planted' => 'decimal:3',
        'total_input_cost' => 'decimal:2',
        'total_harvest_quantity' => 'decimal:3',
        'estimated_harvest_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CropSeason $season): void {
            if (blank($season->season_code)) {
                $season->season_code =
                    'CSE'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            $season->start_date ??= now('Africa/Nairobi')->toDateString();
            $season->planting_date ??= $season->start_date;

            if (auth()->check() && blank($season->created_by)) {
                $season->created_by = auth()->id();
            }
        });

        static::saving(function (CropSeason $season): void {
            if (!$season->cropCatalog || !$season->planting_date) {
                return;
            }

            $plantingDate = \Carbon\Carbon::parse($season->planting_date)->toDateString();

            $dates = app(\App\Services\Crops\CropCalendarService::class)->datesFor(
                $season->cropCatalog,
                $plantingDate
            );

            foreach ($dates as $field => $value) {
                if (blank($season->{$field}) && $value) {
                    $season->{$field} = $value;
                }
            }
        });

        static::saved(function (CropSeason $season): void {
            if ($season->fieldPartition) {
                $season->fieldPartition->forceFill([
                    'status' => $season->status === 'completed' ? 'harvested' : 'planted',
                ])->saveQuietly();
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

    public function harvestRecords()
    {
        return $this->hasMany(CropHarvestRecord::class);
    }

    public function activities()
    {
        return $this->hasMany(CropActivity::class);
    }

    public function careTasks()
    {
        return $this->hasMany(CropCareTask::class);
    }

    public function syncCropTotals(): void
    {
        $this->forceFill([
            'total_input_cost' => $this->inputApplications()->sum('total_cost'),
            'total_harvest_quantity' => $this->harvestRecords()->sum('quantity'),
            'estimated_harvest_value' => $this->harvestRecords()->sum('estimated_value'),
        ])->saveQuietly();
    }

    public function getCropNameAttribute(): string
    {
        return $this->cropCatalog?->display_name ?? 'N/A';
    }

    public function getDaysSincePlantingAttribute(): int
    {
        if (!$this->planting_date) {
            return 0;
        }

        return max(0, $this->planting_date->diffInDays(now('Africa/Nairobi')));
    }

    /* public function getDaysToHarvestAttribute(): ?int
     {
         if (!$this->expected_harvest_from) {
             return null;
         }

         return now('Africa/Nairobi')->diffInDays($this->expected_harvest_from, false);
     }*/

    public function getHarvestStatusAttribute(): string
    {
        if (!$this->expected_harvest_from) {
            return 'Not Scheduled';
        }

        if ($this->status === 'completed') {
            return 'Harvested';
        }

        if ($this->days_to_harvest < 0) {
            return 'Overdue';
        }

        if ($this->days_to_harvest <= 14) {
            return 'Due Soon';
        }

        return 'Scheduled';
    }

    public function getStageInsightAttribute(): array
    {
        return CropStageAdvisor::analyze(
            $this->cropCatalog?->name ?? 'crop',
            $this->growth_stage,
            $this->germination_percent,
            $this->health_status
        );
    }

    public function getStageImageUrlAttribute(): ?string
    {
        return $this->stage_insight['image_url'] ?? null;
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

    /* public function getStageInsightAttribute(): array
     {
         return CropStageAdvisor::analyze(
             cropName: $this->cropCatalog?->name ?? 'crop',
             stage: $this->growth_stage,
             germinationPercent: $this->germination_percent ? (float) $this->germination_percent : null,
             healthStatus: $this->health_status,
             daysSincePlanting: $this->days_since_planting,
             daysToHarvest: $this->days_to_harvest,
         );
     }

    public function getStageImageUrlAttribute(): string
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

    /* public function getWateringAdviceAttribute(): string
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
     }*/

    public function getVisualUrgencyAttribute(): string
    {
        return $this->stage_insight['urgency'] ?? 'gray';
    }

    public function getGrowthProgressPercentAttribute(): int
    {
        if (!$this->planting_date) {
            return 0;
        }

        $daysSincePlanting = max(0, (int) $this->days_since_planting);

        $targetDays = null;

        if ($this->cropCatalog?->maturity_days_max) {
            $targetDays = (int) $this->cropCatalog->maturity_days_max;
        } elseif ($this->expected_harvest_from) {
            $targetDays = max(1, Carbon::parse($this->planting_date)->diffInDays(Carbon::parse($this->expected_harvest_from)));
        }

        if (!$targetDays || $targetDays <= 0) {
            return min(100, $daysSincePlanting > 0 ? 15 : 0);
        }

        return (int) min(100, round(($daysSincePlanting / $targetDays) * 100));
    }

    public function getDaysToHarvestAttribute(): ?int
    {
        if (!$this->expected_harvest_from) {
            return null;
        }

        return (int) now('Africa/Nairobi')
            ->startOfDay()
            ->diffInDays(Carbon::parse($this->expected_harvest_from)->startOfDay(), false);
    }
}
