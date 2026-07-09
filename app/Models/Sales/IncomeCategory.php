<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IncomeCategory extends Model
{
    use SoftDeletes;

    protected $table = 'income_categories';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'type',
        'description',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (IncomeCategory $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }

            if (blank($category->code)) {
                $category->code = static::generateCode($category->type ?? 'other_income');
            }

            if (auth()->check()) {
                $category->created_by = auth()->id();
                $category->updated_by = auth()->id();
            }
        });

        static::updating(function (IncomeCategory $category) {
            if ($category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }

            if (auth()->check()) {
                $category->updated_by = auth()->id();
            }
        });

        static::saving(function (IncomeCategory $category) {
            if ($category->is_default) {
                static::where('id', '!=', $category->id)
                    ->where('type', $category->type)
                    ->update(['is_default' => false]);
            }
        });
    }

    public static function generateCode(string $type): string
    {
        $prefix = match ($type) {
            'animal_sales' => 'ANS',
            'breeder_sales' => 'BRS',
            'cull_sales' => 'CLS',
            'slaughter_sales' => 'SLS',
            'milk_sales' => 'MLS',
            'egg_sales' => 'EGS',
            'crop_sales' => 'CRS',
            'manure_sales' => 'MNS',
            'service_income' => 'SVC',
            default => 'OTH',
        };

        $latestId = static::withTrashed()->max('id') ?? 0;

        return $prefix . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'animal_sales' => 'Animal Sales',
            'breeder_sales' => 'Breeder Sales',
            'cull_sales' => 'Cull Sales',
            'slaughter_sales' => 'Slaughter Sales',
            'milk_sales' => 'Milk Sales',
            'egg_sales' => 'Egg Sales',
            'crop_sales' => 'Crop Sales',
            'manure_sales' => 'Manure Sales',
            'service_income' => 'Service Income',
            default => 'Other Income',
        };
    }
}
