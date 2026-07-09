<?php

namespace App\Support\Audit;

use App\Models\AuditSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuditSessionReportPresenter
{
    public function build(AuditSession $session): array
    {
        $session->loadMissing([
            'logs' => fn($query) => $query
                ->latest('created_at')
                ->limit(300),
        ]);

        $logs = $session
            ->logs
            ->sortByDesc('created_at')
            ->values();

        $primary = $this->hex($this->setting('theme.primary', '#014a12'), '#014a12');
        $secondary = $this->hex($this->setting('theme.secondary', '#111827'), '#111827');
        $accent = $this->hex($this->setting('theme.accent', '#f59e0b'), '#f59e0b');

        $closedAt = $session->logout_at ?: $session->last_seen_at;
        $actorName = trim((string) ($session->actor_label ?: $session->user_name ?: 'Unknown user'));
        $actorEmail = trim((string) ($session->user_email ?: '—'));

        $createdCount = $logs->where('event', 'created')->count();
        $updatedCount = $logs->where('event', 'updated')->count();
        $deletedCount = $logs->whereIn('event', ['deleted', 'force_deleted'])->count();
        $exportedCount = $logs->whereIn('event', ['exported', 'printed'])->count();

        $riskEvents = $logs->filter(fn($log) => in_array(
            (string) $log->event,
            [
                'deleted',
                'force_deleted',
                'failed_login',
                'rejected',
                'stock_adjustment',
                'permission_changed',
                'payment_deleted',
                'payment_updated',
                'backdated_transaction',
            ],
            true,
        ));

        $moduleSummary = $logs
            ->groupBy(fn($log) => trim((string) ($log->module ?: 'System')) ?: 'System')
            ->map(fn(Collection $items, string $module): array => [
                'module' => $module,
                'total' => $items->count(),
                'created' => $items->where('event', 'created')->count(),
                'updated' => $items->where('event', 'updated')->count(),
                'risk' => $items->filter(fn($item) => $riskEvents->contains('id', $item->id))->count(),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values()
            ->all();

        $activities = $logs
            ->take(15)
            ->map(fn($log): array => [
                'time' => $log->created_at?->timezone('Africa/Nairobi')->format('H:i:s') ?: '—',
                'date' => $log->created_at?->timezone('Africa/Nairobi')->format('d M Y') ?: '—',
                'action' => $this->eventLabel($log->event),
                'module' => trim((string) ($log->module ?: 'System')) ?: 'System',
                'description' => trim((string) ($log->description ?: 'No description recorded.')),
                'severity' => strtolower((string) ($log->severity ?: 'info')),
            ])
            ->all();

        $duration = $this->duration($session->login_at, $closedAt, $session->duration_label ?? null);
        $status = $this->label($session->effective_status_label ?? $session->status ?? 'closed');
        $reason = $this->label($session->logout_reason_label ?? $session->logout_reason ?? '—');

        $summary = $logs->isEmpty()
            ? 'No application activity was captured during this session.'
            : sprintf(
                '%s activity record%s captured across %d module%s. %d risk event%s detected.',
                number_format($logs->count()),
                $logs->count() === 1 ? '' : 's',
                count($moduleSummary),
                count($moduleSummary) === 1 ? '' : 's',
                $riskEvents->count(),
                $riskEvents->count() === 1 ? '' : 's',
            );

        // [$logoUrl, $logoDataUri] = $this->logo();
        [$logoUrl, $logoDataUri, $logoPath] = $this->logo();

        return [
            'farm_name' => (string) $this->setting('farm.name', config('app.name', 'Lelekwe Farm ERP')),
            'farm_tagline' => (string) $this->setting('farm.tagline', 'Farm Intelligence, Operations & Accountability'),
            'farm_email' => (string) $this->setting('farm.email', config('mail.from.address', '')),
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent,
            'logo_url' => $logoUrl,
            'logo_data_uri' => $logoDataUri,
            'logo_path' => $logoPath,
            'session_id' => $session->getKey(),
            'session_uuid' => (string) ($session->uuid ?: ''),
            'reference' => 'AUD-' . str_pad((string) $session->getKey(), 6, '0', STR_PAD_LEFT),
            'actor_name' => $actorName,
            'actor_initials' => $this->initials($actorName),
            'actor_email' => $actorEmail,
            'status' => $status,
            'status_is_active' => (string) $session->status === 'active',
            'close_reason' => $reason,
            'login_at' => $session->login_at?->timezone('Africa/Nairobi')->format('d M Y, H:i:s') ?: '—',
            'closed_at' => $closedAt?->timezone('Africa/Nairobi')->format('d M Y, H:i:s') ?: '—',
            'duration' => $duration,
            'request_count' => (int) $session->request_count,
            'event_count' => $logs->count(),
            'risk_count' => $riskEvents->count(),
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'deleted_count' => $deletedCount,
            'exported_count' => $exportedCount,
            'ip_address' => (string) ($session->ip_address ?: '—'),
            'user_agent' => (string) ($session->user_agent ?: '—'),
            'first_url' => $this->displayUrl($session->first_url),
            'last_url' => $this->displayUrl($session->last_url),
            'summary' => $summary,
            'modules' => $moduleSummary,
            'activities' => $activities,
            'generated_at' => now('Africa/Nairobi')->format('d M Y, H:i:s'),
        ];
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return function_exists('setting') ? setting($key, $default) : $default;
    }

    private function hex(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
    }

    private function label(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        return Str::headline(str_replace(['-', '_'], ' ', $value));
    }

    private function eventLabel(mixed $event): string
    {
        return $this->label($event ?: 'activity');
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', $name))
            ->filter()
            ->map(fn(string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('') ?: 'U';
    }

    private function duration(mixed $loginAt, mixed $closedAt, mixed $accessor): string
    {
        if (filled($accessor)) {
            return (string) $accessor;
        }

        if (!$loginAt || !$closedAt) {
            return '—';
        }

        $minutes = Carbon::parse($loginAt)->diffInMinutes(Carbon::parse($closedAt));

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ($remaining ? ' ' . $remaining . ' min' : '');
    }

    private function displayUrl(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '—';
        }

        $parts = parse_url($url);

        if (!$parts) {
            return Str::limit($url, 110);
        }

        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '/';

        return Str::limit(($host ? $host : '') . $path, 110);
    }

    private function logo(): array
    {
        $configured = trim((string) $this->setting('branding.logo_light', ''));

        /*
         * Accept both:
         * branding/logo.png
         * storage/branding/logo.png
         * https://domain.com/storage/branding/logo.png
         */
        $pathFromUrl = parse_url($configured, PHP_URL_PATH);
        $relativePath = ltrim($pathFromUrl ?: $configured, '/');

        if (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, strlen('storage/'));
        }

        $candidates = array_filter([
            $relativePath ? storage_path('app/public/' . $relativePath) : null,
            $relativePath ? public_path('storage/' . $relativePath) : null,
            public_path('images/logo.png'),
        ]);

        foreach ($candidates as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'image/png';

            $dataUri = 'data:' . $mime . ';base64,'
                . base64_encode((string) file_get_contents($path));

            $url = filter_var($configured, FILTER_VALIDATE_URL)
                ? $configured
                : ($relativePath
                    ? asset('storage/' . $relativePath)
                    : asset('images/logo.png'));

            return [$url, $dataUri, $path];
        }

        return [null, null, null];
    }
}
