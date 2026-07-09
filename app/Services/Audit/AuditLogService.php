<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\AuditSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogService
{
    public function log(
        string $event,
        ?string $module = null,
        ?string $description = null,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?string $severity = null,
        ?Request $request = null,
        ?int $responseStatus = null,
    ): ?AuditLog {
        if (!config('audit.enabled', true)) {
            logger()->warning('AUDIT SKIPPED: audit.enabled is false');

            return null;
        }

        if (!Schema::hasTable('audit_logs')) {
            logger()->error('AUDIT SKIPPED: audit_logs table does not exist');

            return null;
        }

        try {
            $request ??= request();

            $user = Auth::user();
            $sessionId = session('audit_session_id');

            $module ??= $auditable
                ? $this->moduleForModel($auditable)
                : $this->moduleFromRequest($request);

            $severity ??= $this->severityForEvent($event);

            $payload = [
                'uuid' => (string) Str::uuid(),
                'guard' => Auth::getDefaultDriver(),
                'audit_session_uuid' => $this->currentAuditSessionUuid($sessionId),
                'audit_session_id' => $sessionId,
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_email' => $user?->email,
                'event' => $event,
                'module' => $module,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'record_label' => $auditable ? $this->recordLabel($auditable) : null,
                'description' => $description,
                'old_values' => $this->sanitize($oldValues),
                'new_values' => $this->sanitize($newValues),
                'metadata' => $this->sanitize($metadata),
                'severity' => $severity,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'route_name' => $request?->route()?->getName(),
                'http_method' => $request?->method(),
                'response_status' => $responseStatus,
            ];

            $payload = $this->onlyExistingColumns('audit_logs', $payload);

            $log = AuditLog::query()->create($payload);

            if ($sessionId && Schema::hasTable('audit_sessions')) {
                AuditSession::query()
                    ->whereKey($sessionId)
                    ->update([
                        'event_count' => DB::raw('COALESCE(event_count, 0) + 1'),
                        'last_seen_at' => now('Africa/Nairobi'),
                        'updated_at' => now(),
                    ]);
            }

            return $log;
        } catch (\Throwable $e) {
            logger()->error('AUDIT LOG FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'event' => $event,
                'module' => $module,
                'description' => $description,
            ]);

            report($e);

            if ((bool) env('AUDIT_THROW_ERRORS', false)) {
                throw $e;
            }

            return null;
        }
    }

    public function logSystem(
        string $event,
        string $module = 'System',
        ?string $description = null,
        array $metadata = [],
        ?string $severity = null,
    ): ?AuditLog {
        return $this->log(
            event: $event,
            module: $module,
            description: $description,
            metadata: $metadata,
            severity: $severity,
            request: request(),
        );
    }

    protected function currentAuditSessionUuid(?int $sessionId): ?string
    {
        if (!$sessionId) {
            return null;
        }

        try {
            return \App\Models\AuditSession::query()
                ->whereKey($sessionId)
                ->value('uuid');
        } catch (\Throwable) {
            return null;
        }
    }

    public function logPageView(Request $request, ?int $responseStatus = null): ?AuditLog
    {
        if (!config('audit.track_page_views', true)) {
            return null;
        }

        if (!$request->isMethod('GET')) {
            return null;
        }

        if ($this->shouldSkipRequest($request)) {
            return null;
        }

        return $this->log(
            event: 'page_view',
            module: $this->moduleFromRequest($request),
            description: 'Viewed page: ' . ($request->route()?->getName() ?: $request->path()),
            metadata: [
                'path' => $request->path(),
                'query' => $request->query(),
            ],
            severity: 'info',
            request: $request,
            responseStatus: $responseStatus,
        );
    }

    public function logFailedRequest(Request $request, int $status): ?AuditLog
    {
        if (!config('audit.track_failed_requests', true)) {
            return null;
        }

        if ($this->shouldSkipRequest($request)) {
            return null;
        }

        if ($status < 400) {
            return null;
        }

        return $this->log(
            event: 'failed_request',
            module: $this->moduleFromRequest($request),
            description: 'Request returned HTTP ' . $status,
            metadata: [
                'path' => $request->path(),
                'query' => $request->query(),
            ],
            severity: $status >= 500 ? 'danger' : 'warning',
            request: $request,
            responseStatus: $status,
        );
    }

    public function logModelEvent(Model $model, string $event): ?AuditLog
    {
        if (!config('audit.track_models', true)) {
            return null;
        }

        if ($this->shouldSkipModel($model)) {
            return null;
        }

        [$oldValues, $newValues] = $this->modelChanges($model, $event);

        if ($event === 'updated' && blank($oldValues) && blank($newValues)) {
            return null;
        }

        $label = $this->recordLabel($model);
        $module = $this->moduleForModel($model);

        return $this->log(
            event: $event,
            module: $module,
            description: $this->descriptionForModelEvent($model, $event, $label),
            auditable: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: [
                'model' => $model::class,
                'table' => $model->getTable(),
                'primary_key' => $model->getKeyName(),
                'primary_value' => $model->getKey(),
            ],
            severity: $this->severityForEvent($event),
        );
    }

    public function logPrinted(
        string $module,
        string $description,
        ?Model $auditable = null,
        array $metadata = [],
    ): ?AuditLog {
        return $this->log(
            event: 'printed',
            module: $module,
            description: $description,
            auditable: $auditable,
            metadata: $metadata,
            severity: 'info',
        );
    }

    public function logExported(
        string $module,
        string $description,
        ?Model $auditable = null,
        array $metadata = [],
    ): ?AuditLog {
        return $this->log(
            event: 'exported',
            module: $module,
            description: $description,
            auditable: $auditable,
            metadata: $metadata,
            severity: 'info',
        );
    }

    public function logAction(
        string $event,
        string $module,
        string $description,
        ?Model $auditable = null,
        array $metadata = [],
        ?string $severity = null,
    ): ?AuditLog {
        return $this->log(
            event: $event,
            module: $module,
            description: $description,
            auditable: $auditable,
            metadata: $metadata,
            severity: $severity,
        );
    }

    public function shouldSkipRequest(Request $request): bool
    {
        foreach (config('audit.ignored_paths', []) as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, config('audit.ignored_route_names', []), true)) {
            return true;
        }

        if (!config('audit.track_livewire_requests', false) && str_contains($request->path(), 'livewire')) {
            return true;
        }

        return false;
    }

    public function shouldSkipModel(Model $model): bool
    {
        foreach (config('audit.excluded_models', []) as $excluded) {
            if ($model instanceof $excluded) {
                return true;
            }
        }

        return false;
    }

    protected function modelChanges(Model $model, string $event): array
    {
        if ($event === 'created') {
            return [
                [],
                $this->sanitize($model->getAttributes()),
            ];
        }

        if ($event === 'updated') {
            $changes = Arr::except($model->getChanges(), config('audit.excluded_fields', []));

            $old = [];
            $new = [];

            foreach ($changes as $field => $value) {
                $old[$field] = $model->getOriginal($field);
                $new[$field] = $value;
            }

            return [
                $this->sanitize($old),
                $this->sanitize($new),
            ];
        }

        if (in_array($event, ['deleted', 'force_deleted', 'restored'], true)) {
            return [
                $this->sanitize($model->getOriginal()),
                [],
            ];
        }

        return [[], []];
    }

    protected function sanitize(array $values): array
    {
        $excluded = config('audit.excluded_fields', []);

        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array($key, $excluded, true)) {
                $clean[$key] = '[hidden]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
                continue;
            }

            if (is_string($value) && strlen($value) > 1200) {
                $clean[$key] = Str::limit($value, 1200);
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    protected function severityForEvent(string $event): string
    {
        if (in_array($event, config('audit.high_risk_events', []), true)) {
            return in_array($event, ['force_deleted', 'payment_deleted', 'failed_request'], true)
                ? 'danger'
                : 'warning';
        }

        return match ($event) {
            'created', 'login', 'restored' => 'success',
            'failed_login', 'deleted', 'cancelled', 'rejected' => 'warning',
            'force_deleted', 'failed_request' => 'danger',
            default => 'info',
        };
    }

    protected function moduleForModel(Model $model): string
    {
        $basename = class_basename($model);

        return config("audit.model_modules.{$basename}")
            ?? str($basename)->headline()->toString();
    }

    protected function moduleFromRequest(Request $request): string
    {
        $routeName = $request->route()?->getName() ?: '';
        $path = $request->path();

        $source = $routeName . ' ' . $path;

        return match (true) {
            str_contains($source, 'livestock') => 'Livestock',
            str_contains($source, 'procurement') => 'Procurement',
            str_contains($source, 'inventory') => 'Inventory',
            str_contains($source, 'sales') => 'Sales',
            str_contains($source, 'human-resource'), str_contains($source, 'hr') => 'Human Resource',
            str_contains($source, 'asset') => 'Asset Valuation',
            str_contains($source, 'crop') => 'Crop Farming',
            str_contains($source, 'audit') => 'Audit',
            default => 'System',
        };
    }

    protected function recordLabel(Model $model): string
    {
        foreach ([
            'name',
            'title',
            'tag_number',
            'animal_tag',
            'invoice_number',
            'receipt_no',
            'payment_number',
            'purchase_order_number',
            'grn_number',
            'supplier_name',
            'customer_name',
            'employee_number',
            'asset_number',
            'code',
            'reference_no',
            'number',
        ] as $field) {
            if (filled($model->{$field} ?? null)) {
                return (string) $model->{$field};
            }
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    protected function descriptionForModelEvent(Model $model, string $event, string $label): string
    {
        $modelName = str(class_basename($model))->headline()->toString();

        return match ($event) {
            'created' => "{$modelName} created: {$label}",
            'updated' => "{$modelName} updated: {$label}",
            'deleted' => "{$modelName} deleted: {$label}",
            'force_deleted' => "{$modelName} permanently deleted: {$label}",
            'restored' => "{$modelName} restored: {$label}",
            default => "{$modelName} {$event}: {$label}",
        };
    }

    protected function onlyExistingColumns(string $table, array $payload): array
    {
        $columns = Schema::getColumnListing($table);

        return collect($payload)
            ->only($columns)
            ->all();
    }
}
