<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transfer_number',
        'from_location_id',
        'to_location_id',
        'transfer_date',
        'expected_receive_date',
        'received_at',
        'status',
        'reason',
        'notes',
        'receive_notes',
        'cancel_reason',
        'prepared_by_id',
        'received_by_id',
        'cancelled_by_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_receive_date' => 'date',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnimalTransfer $transfer): void {
            if (blank($transfer->transfer_number)) {
                $date = now('Africa/Nairobi')->format('Ymd');

                $next = static::query()
                    ->whereDate('created_at', today('Africa/Nairobi'))
                    ->count() + 1;

                $transfer->transfer_number = 'TRF-' . $date . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }

            $transfer->created_by ??= auth()->id();
            $transfer->prepared_by_id ??= auth()->id();
        });

        static::updating(function (AnimalTransfer $transfer): void {
            $transfer->updated_by = auth()->id();
        });
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AnimalTransferItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function complete(?string $notes = null): void
    {
        $this->loadMissing(['items.animal']);

        foreach ($this->items as $item) {
            if (! $item->animal) {
                continue;
            }

            $item->animal->update([
                'current_location_id' => $this->to_location_id,
            ]);

            $item->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
        }

        $this->update([
            'status' => 'completed',
            'received_at' => now(),
            'received_by_id' => auth()->id(),
            'receive_notes' => $notes,
        ]);
    }
}
