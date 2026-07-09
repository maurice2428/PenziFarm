<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditSession;
use App\Support\Audit\AuditSessionReportPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditSessionEmailPdfController extends Controller
{
    public function __invoke(
        AuditSession $auditSession,
        AuditSessionReportPresenter $presenter,
    ): StreamedResponse {
        $report = $presenter->build($auditSession);

        $pdf = Pdf::loadView('pdf.audit.session-summary', [
            'report' => $report,
        ])->setPaper('a4', 'portrait');

        $filename = 'audit-session-' . $auditSession->getKey() . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            [
                'Content-Type' => 'application/pdf',
            ],
        );
    }
}
