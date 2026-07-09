<?php

namespace App\Http\Controllers;

use App\Models\AuditSession;
use App\Services\Audit\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AuditSessionReportController extends Controller
{
    public function __invoke(AuditSession $auditSession): Response
    {
        abort_unless(
            auth()->user()?->can('view audit reports') ||
                auth()->user()?->can('view audit logs') ||
                auth()->user()?->hasRole('Admin') ||
                auth()->user()?->hasRole('Administrator'),
            403
        );

        $auditSession->load([
            'logs' => fn($query) => $query->orderBy('created_at'),
            'user',
        ]);

        app(AuditLogService::class)->logPrinted(
            module: 'Audit',
            description: 'Printed audit session report for ' . $auditSession->actor_label,
            auditable: $auditSession,
            metadata: [
                'audit_session_uuid' => $auditSession->uuid,
                'user_email' => $auditSession->user_email,
                'report' => 'Audit Session Report',
            ],
        );

        $generatedBy = auth()->user();

        $generatedByRole = 'User';

        if ($generatedBy && method_exists($generatedBy, 'getRoleNames')) {
            $generatedByRole = $generatedBy->getRoleNames()->first() ?: 'User';
        }

        $pdf = Pdf::loadView('pdfs.audit.session-report', [
            'session' => $auditSession,
            'logs' => $auditSession->logs,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('audit-session-report-' . $auditSession->id . '.pdf');
    }
}
