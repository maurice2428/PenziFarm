<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditSession extends Model
{
    protected $fillable = [
        'uuid',
        'laravel_session_id',
        'user_id',
        'user_name',
        'user_email',
        'guard',
        'status',
        'login_at',
        'logout_at',
        'last_seen_at',
        'expires_at',
        'logout_reason',
        'ip_address',
        'user_agent',
        'first_url',
        'last_url',
        'request_count',
        'event_count',
        'email_to',
        'emailed_at',
        'summary',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
        'emailed_at' => 'datetime',
        'request_count' => 'integer',
        'event_count' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'audit_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now('Africa/Nairobi')->timezone(config('app.timezone')));
    }

    public function scopeExpiredOpen(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now('Africa/Nairobi')->timezone(config('app.timezone')));
    }

    public function getActorLabelAttribute(): string
    {
        return $this->user_name
            ?: $this->user_email
            ?: $this->user?->name
            ?: 'System';
    }

    public function getIsAvailableAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->gt(now('Africa/Nairobi'));
    }

    public function getIsExpiredOpenAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->lte(now('Africa/Nairobi'));
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'closed') {
            return 'closed';
        }

        if ($this->is_expired_open) {
            return 'expired';
        }

        if ($this->is_available) {
            return 'active';
        }

        return $this->status ?: 'unknown';
    }

    public function getEffectiveStatusLabelAttribute(): string
    {
        return str($this->effective_status)
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getEffectiveStatusColorAttribute(): string
    {
        return match ($this->effective_status) {
            'active' => 'success',
            'expired' => 'warning',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->effective_status_label;
    }

    public function getLogoutReasonLabelAttribute(): string
    {
        return str($this->logout_reason ?: $this->effective_status ?: 'active')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getEmailStatusLabelAttribute(): string
    {
        return $this->emailed_at ? 'Sent' : 'Not Sent';
    }

    public function getDurationLabelAttribute(): string
    {
        if (!$this->login_at) {
            return '0 sec';
        }

        $end = $this->logout_at
            ?: $this->last_seen_at
            ?: now('Africa/Nairobi');

        $seconds = (int) max(0, $this->login_at->diffInSeconds($end));

        if ($seconds < 60) {
            return $seconds . ' sec';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return trim($hours . ' hr ' . ($remainingMinutes > 0 ? $remainingMinutes . ' min' : ''));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->effective_status === 'active';
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->effective_status === 'closed';
    }
}
