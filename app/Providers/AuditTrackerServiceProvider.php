<?php

namespace App\Providers;

use App\Services\Audit\AuditLogService;
use App\Services\Audit\AuditSessionService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! config('audit.enabled', true)) {
            return;
        }

        $this->registerAuthTracking();
        $this->registerModelTracking();
    }

    protected function registerAuthTracking(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(AuditSessionService::class)->startSession(request());
        });

        Event::listen(Logout::class, function (Logout $event): void {
            app(AuditSessionService::class)->closeCurrentSession('logout', true);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            app(AuditLogService::class)->log(
                event: 'failed_login',
                module: 'Authentication',
                description: 'Failed login attempt: ' . ($event->credentials['email'] ?? 'Unknown'),
                metadata: [
                    'email' => $event->credentials['email'] ?? null,
                ],
                severity: 'warning',
                request: request(),
            );
        });
    }

    protected function registerModelTracking(): void
    {
        if (! config('audit.track_models', true)) {
            return;
        }

        $events = [
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'restored' => 'restored',
            'forceDeleted' => 'force_deleted',
        ];

        foreach ($events as $eloquentEvent => $auditEvent) {
            Event::listen("eloquent.{$eloquentEvent}: *", function (string $eventName, array $payload) use ($auditEvent): void {
                $model = $payload[0] ?? null;

                if (! $model instanceof Model) {
                    return;
                }

                app(AuditLogService::class)->logModelEvent($model, $auditEvent);
            });
        }
    }
}
