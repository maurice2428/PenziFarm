<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasualPayroll extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_name',
        'title',
        'week_start',
        'week_end',
        'work_site',
        'notes',
        'total_casuals',
        'total_days_worked',
        'total_amount',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'total_casuals' => 'integer',
        'total_days_worked' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CasualPayrollItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function recalculateTotals(): void
{
    $items = $this->items()
        ->whereNull('deleted_at')
        ->get();

    $this->forceFill([
        'total_casuals' => $items->count(),
        'total_days_worked' => $items->sum(fn ($item) => (float) $item->days_worked),
        'total_amount' => $items->sum(fn ($item) => (float) $item->total_pay),
    ])->save();
}
}
