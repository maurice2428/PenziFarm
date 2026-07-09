<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\AuditSession;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AuditDashboard extends Page
{
    protected static ?string $navigationGroup = 'Audit Logs';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'system/audit-dashboard';

    protected static string $view = 'filament.pages.audit-dashboard';

    public function getTitle(): string|Htmlable
    {
        return 'Audit Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::SevenExtraLarge;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view audit logs') ||
            auth()->user()?->can('view audit dashboard') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    protected function getViewData(): array
    {
        $todayStart = Carbon::now('Africa/Nairobi')
            ->startOfDay()
            ->timezone(config('app.timezone'));

        $todayEnd = Carbon::now('Africa/Nairobi')
            ->endOfDay()
            ->timezone(config('app.timezone'));

        $latestLogs = AuditLog::query()
            ->with(['user', 'auditSession'])
            ->latest('created_at')
            ->limit(80)
            ->get();

        app(\App\Services\Audit\AuditSessionService::class)->closeExpiredSessions();

        $activeSessions = AuditSession::query()
            ->with('user')
            ->available()
            ->latest('last_seen_at')
            ->limit(30)
            ->get();

        $topUsers = AuditLog::query()
            ->select([
                DB::raw("COALESCE(NULLIF(user_name, ''), NULLIF(user_email, ''), 'System') as actor"),
                DB::raw('COUNT(*) as total'),
            ])
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->groupBy('actor')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $totalToday = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $failedLogins = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('event', 'failed_login')
            ->count();

        $deletedRecords = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->whereIn('event', ['deleted', 'force_deleted'])
            ->count();

        $printedReports = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('event', 'printed')
            ->count();

        $exportedReports = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('event', 'exported')
            ->count();

        $highRiskToday = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where(function ($query) {
                $query
                    ->whereIn('severity', ['danger', 'warning'])
                    ->orWhereIn('event', [
                        'failed_login',
                        'deleted',
                        'force_deleted',
                        'permission_changed',
                        'role_changed',
                        'payment_deleted',
                        'payment_updated',
                        'stock_adjustment',
                        'cancelled',
                        'rejected',
                    ]);
            })
            ->count();

        $closedToday = AuditSession::query()
            ->where('status', 'closed')
            ->whereBetween('logout_at', [$todayStart, $todayEnd])
            ->count();

        return [
            'latestLogs' => $latestLogs,
            'activeSessions' => $activeSessions,
            'topUsers' => $topUsers,
            'stats' => [
                'totalToday' => $totalToday,
                'highRiskToday' => $highRiskToday,
                'activeSessions' => $activeSessions->count(),
                'closedToday' => $closedToday,
                'failedLogins' => $failedLogins,
                'deletedRecords' => $deletedRecords,
                'printedReports' => $printedReports,
                'exportedReports' => $exportedReports,
            ],
        ];
    }
}
