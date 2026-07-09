<?php

namespace App\Services\Audit;

use App\Mail\AuditSessionSummaryMail;
use App\Models\AuditSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditSessionService
{
    public function startSession(?Request $request = null): ?AuditSession
    {
        if (!config('audit.enabled', true)) {
            return null;
        }

        $request ??= request();

        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $now = now('Africa/Nairobi');

        $existingId = session('audit_session_id');

        if ($existingId) {
            $existing = AuditSession::query()
                ->whereKey($existingId)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                $this->touchAuditSessionRecord($existing, $request);

                return $existing->refresh();
            }
        }

        $session = AuditSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'laravel_session_id' => $this->currentLaravelSessionId($request),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'guard' => Auth::getDefaultDriver(),
            'status' => 'active',
            'login_at' => $now,
            'last_seen_at' => $now,
            'expires_at' => $now->copy()->addMinutes((int) config('audit.session_lifetime_minutes', 120)),
            'logout_at' => null,
            'logout_reason' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'first_url' => $request->fullUrl(),
            'last_url' => $request->fullUrl(),
            'request_count' => 0,
            'event_count' => 0,
            'email_to' => null,
            'emailed_at' => null,
            'summary' => null,
        ]);

        session(['audit_session_id' => $session->id]);

        app(AuditLogService::class)->log(
            event: 'login',
            module: 'Authentication',
            description: 'User logged in: ' . ($user->email ?? 'Unknown'),
            auditable: $session,
            metadata: [
                'user_id' => $user->id,
                'email' => $user->email,
                'guard' => Auth::getDefaultDriver(),
                'laravel_session_id' => $session->laravel_session_id,
            ],
            severity: 'success',
            request: $request,
        );

        return $session;
    }

    public function currentSession(?Request $request = null): ?AuditSession
    {
        $request ??= request();

        $id = session('audit_session_id');

        if ($id) {
            $session = AuditSession::query()
                ->whereKey($id)
                ->where('status', 'active')
                ->first();

            if ($session) {
                return $session;
            }

            session()->forget('audit_session_id');
        }

        if (!Auth::check()) {
            return null;
        }

        return $this->startSession($request);
    }

    public function touchCurrentSession(?Request $request = null): ?AuditSession
    {
        if (!config('audit.enabled', true)) {
            return null;
        }

        if (!Auth::check()) {
            return null;
        }

        $request ??= request();

        $session = $this->currentSession($request);

        if (!$session) {
            return null;
        }

        $this->touchAuditSessionRecord($session, $request);

        return AuditSession::query()->find($session->id);
    }

    protected function touchAuditSessionRecord(AuditSession $session, Request $request): void
    {
        $now = now('Africa/Nairobi');

        AuditSession::query()
            ->whereKey($session->id)
            ->where('status', 'active')
            ->update([
                'laravel_session_id' => $this->currentLaravelSessionId($request),
                'last_seen_at' => $now,
                'expires_at' => $now->copy()->addMinutes((int) config('audit.session_lifetime_minutes', 120)),
                'last_url' => $request->fullUrl(),
                'request_count' => DB::raw('COALESCE(request_count, 0) + 1'),
                'updated_at' => now(),
            ]);
    }

    public function closeCurrentSession(string $reason = 'logout', bool $sendEmail = true): ?AuditSession
    {
        $id = session('audit_session_id');

        if (!$id) {
            return null;
        }

        $session = AuditSession::query()
            ->whereKey($id)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            session()->forget('audit_session_id');

            return null;
        }

        $closed = $this->closeSession(
            session: $session,
            reason: $reason,
            sendEmail: $sendEmail,
            destroyLaravelSession: false,
        );

        session()->forget('audit_session_id');

        return $closed;
    }

    public function endCurrentSession(string $reason = 'logout', bool $sendEmail = true): ?AuditSession
    {
        return $this->closeCurrentSession($reason, $sendEmail);
    }

    public function closeSession(
        AuditSession $session,
        string $reason = 'manual',
        bool $sendEmail = true,
        bool $destroyLaravelSession = false,
    ): AuditSession {
        if ($session->status !== 'active') {
            return $session;
        }

        $now = now('Africa/Nairobi');

        $session->forceFill([
            'status' => 'closed',
            'logout_reason' => $reason,
            'logout_at' => $now,
            'last_seen_at' => $session->last_seen_at ?: $now,
            'expires_at' => null,
        ])->save();

        $destroyedLaravelSession = false;

        if ($destroyLaravelSession) {
            $destroyedLaravelSession = $this->destroyLaravelSession($session);
        }

        app(AuditLogService::class)->log(
            event: 'logout',
            module: 'Authentication',
            description: 'Audit session closed for ' . $session->actor_label,
            auditable: $session,
            metadata: [
                'reason' => $reason,
                'duration' => $session->duration_label,
                'target_user_id' => $session->user_id,
                'target_user_email' => $session->user_email,
                'laravel_session_id' => $session->laravel_session_id,
                'laravel_session_destroyed' => $destroyedLaravelSession,
            ],
            severity: match ($reason) {
                'forced' => 'warning',
                'session_expired', 'expired' => 'warning',
                default => 'info',
            },
            request: request(),
        );

        if ($sendEmail) {
            $this->sendSessionEmail($session);
        }

        return AuditSession::query()->find($session->id) ?? $session;
    }

    public function closeExpiredSessions(): int
    {
        $count = 0;

        AuditSession::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now('Africa/Nairobi')->timezone(config('app.timezone')))
            ->limit(100)
            ->get()
            ->each(function (AuditSession $session) use (&$count): void {
                $this->closeSession(
                    session: $session,
                    reason: 'session_expired',
                    sendEmail: false,
                    destroyLaravelSession: true,
                );

                $count++;
            });

        return $count;
    }

    public function destroyLaravelSession(AuditSession $session): bool
    {
        try {
            if (!Schema::hasTable('sessions')) {
                return false;
            }

            $deleted = 0;

            if (filled($session->laravel_session_id)) {
                $deleted += DB::table('sessions')
                    ->where('id', $session->laravel_session_id)
                    ->delete();
            }

            /*
             * Fallback for old audit sessions created before laravel_session_id existed.
             * This may log out all active browser sessions for that user.
             */
            if (
                $deleted === 0 &&
                filled($session->user_id) &&
                Schema::hasColumn('sessions', 'user_id')
            ) {
                $deleted += DB::table('sessions')
                    ->where('user_id', $session->user_id)
                    ->delete();
            }

            return $deleted > 0;
        } catch (\Throwable $e) {
            report($e);

            logger()->error('DESTROY LARAVEL SESSION FAILED', [
                'audit_session_id' => $session->id,
                'laravel_session_id' => $session->laravel_session_id ?? null,
                'user_id' => $session->user_id ?? null,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function defaultAuditEmail(): ?string
    {
        try {
            if (class_exists(\App\Models\AuditSetting::class)) {
                return \App\Models\AuditSetting::get('default_email', config('mail.from.address'));
            }

            return config('mail.from.address');
        } catch (\Throwable) {
            return config('mail.from.address');
        }
    }

    public function sendSessionEmail(AuditSession $session, ?string $email = null): bool
    {
        $email ??= $this->defaultAuditEmail();

        if (!$email) {
            return false;
        }

        try {
            if (
                class_exists(AuditSessionSummaryMail::class) &&
                Schema::hasTable('audit_logs') &&
                Schema::hasColumn('audit_logs', 'audit_session_id')
            ) {
                $session->load([
                    'logs' => fn($query) => $query->latest('created_at')->limit(500),
                ]);

                if (class_exists(\App\Services\Mail\GlobalMailConfigurator::class)) {
                    app(\App\Services\Mail\GlobalMailConfigurator::class)->apply();
                }

                // Mail::to($email)->send(new AuditSessionSummaryMail($session));
                $sent = app(\App\Services\Mail\GlobalMailService::class)
                    ->sendMailable($email, new AuditSessionSummaryMail($session));

                if (!$sent) {
                    return false;
                }
            }

            $session->forceFill([
                'email_to' => $email,
                'emailed_at' => now('Africa/Nairobi'),
            ])->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            $session->forceFill([
                'email_to' => $email,
                'summary' => trim(($session->summary ?: '') . "\n\nEmail failed: " . $e->getMessage()),
            ])->save();

            logger()->error('AUDIT SESSION EMAIL FAILED', [
                'audit_session_id' => $session->id,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function currentUuid(): ?string
    {
        $id = session('audit_session_id');

        if (!$id) {
            return null;
        }

        return AuditSession::query()
            ->whereKey($id)
            ->value('uuid');
    }

    protected function currentLaravelSessionId(?Request $request = null): ?string
    {
        try {
            $request ??= request();

            if (!$request->hasSession()) {
                return null;
            }

            return $request->session()->getId();
        } catch (\Throwable) {
            return null;
        }
    }
}
