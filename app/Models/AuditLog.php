<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $fillable = [
        'audit_session_id',
        'uuid',
        'user_id',
        'user_name',
        'user_email',
        'event',
        'module',
        'severity',
        'auditable_type',
        'auditable_id',
        'record_label',
        'auditable_label',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'route_name',
        'url',
        'http_method',
        'response_status',
        'ip_address',
        'user_agent',
        'guard',
        'batch_uuid',
        'audit_session_uuid',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function auditSession(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class, 'audit_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActorLabelAttribute(): string
    {
        return $this->user_name
            ?: $this->user_email
            ?: $this->user?->name
            ?: 'System';
    }

    public function getEventLabelAttribute(): string
    {
        return str($this->event ?: 'event')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getSeverityLabelAttribute(): string
    {
        return str($this->severity ?: 'info')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getModuleLabelAttribute(): string
    {
        return str($this->module ?: 'System')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getRecordDisplayAttribute(): string
    {
        return $this->record_label
            ?: $this->auditable_label
            ?: ($this->auditable_type ? class_basename($this->auditable_type) . ' #' . $this->auditable_id : '-');
    }

    public function getOldValuesDisplayAttribute(): string
    {
        return $this->prettyJson($this->old_values);
    }

    public function getNewValuesDisplayAttribute(): string
    {
        return $this->prettyJson($this->new_values);
    }

    public function getMetadataDisplayAttribute(): string
    {
        return $this->prettyJson($this->metadata);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->severity) {
            'success' => 'success',
            'warning' => 'warning',
            'danger' => 'danger',
            'info' => 'info',
            default => 'gray',
        };
    }

    protected function prettyJson(mixed $value): string
    {
        if (blank($value)) {
            return '{}';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        }

        return (string) $value;
    }
}
