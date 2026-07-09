<?php

namespace App\Models\Concerns;

use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogService::class)->log(
                event: 'created',
                module: method_exists($model, 'getAuditModule') ? $model->getAuditModule() : null,
                description: static::auditDescription($model, 'created'),
                auditable: $model,
                newValues: static::auditNewValues($model),
                severity: 'success',
            );
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();

            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $oldValues = [];

            foreach ($changes as $key => $value) {
                $oldValues[$key] = $model->getOriginal($key);
            }

            app(AuditLogService::class)->log(
                event: 'updated',
                module: method_exists($model, 'getAuditModule') ? $model->getAuditModule() : null,
                description: static::auditDescription($model, 'updated'),
                auditable: $model,
                oldValues: static::filterAuditValues($model, $oldValues),
                newValues: static::filterAuditValues($model, $changes),
                severity: 'info',
            );
        });

        static::deleted(function (Model $model): void {
            app(AuditLogService::class)->log(
                event: 'deleted',
                module: method_exists($model, 'getAuditModule') ? $model->getAuditModule() : null,
                description: static::auditDescription($model, 'deleted'),
                auditable: $model,
                oldValues: static::auditNewValues($model),
                severity: 'warning',
            );
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model): void {
                app(AuditLogService::class)->log(
                    event: 'restored',
                    module: method_exists($model, 'getAuditModule') ? $model->getAuditModule() : null,
                    description: static::auditDescription($model, 'restored'),
                    auditable: $model,
                    newValues: static::auditNewValues($model),
                    severity: 'success',
                );
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (Model $model): void {
                app(AuditLogService::class)->log(
                    event: 'force_deleted',
                    module: method_exists($model, 'getAuditModule') ? $model->getAuditModule() : null,
                    description: static::auditDescription($model, 'force deleted'),
                    auditable: $model,
                    oldValues: static::auditNewValues($model),
                    severity: 'danger',
                );
            });
        }
    }

    protected static function auditDescription(Model $model, string $event): string
    {
        $label = method_exists($model, 'getAuditLabel')
            ? $model->getAuditLabel()
            : class_basename($model) . ' #' . $model->getKey();

        return str(class_basename($model))->headline() . " {$event}: {$label}";
    }

    protected static function auditNewValues(Model $model): array
    {
        return static::filterAuditValues($model, $model->getAttributes());
    }

    protected static function filterAuditValues(Model $model, array $values): array
    {
        $defaultExcluded = [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $modelExcluded = property_exists($model, 'auditExclude')
            ? (array) $model->auditExclude
            : [];

        $excluded = array_merge($defaultExcluded, $modelExcluded);

        return collect($values)
            ->reject(fn ($value, $key): bool => in_array($key, $excluded, true))
            ->toArray();
    }
}
