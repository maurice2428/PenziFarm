<?php

namespace App\Mail;

use App\Models\AuditSession;
use App\Support\Audit\AuditSessionReportPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AuditSessionSummaryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuditSession $session,
    ) {
    }

    public function envelope(): Envelope
    {
        $name = trim((string) ($this->session->actor_label ?: $this->session->user_name ?: 'User'));

        return new Envelope(
            subject: 'Audit session report · ' . $name . ' · ' . now('Africa/Nairobi')->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.audit.session-summary',
            with: [
                'report' => $this->report(),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('pdf.audit.session-summary', [
                    'report' => $this->report(),
                ])->setPaper('a4', 'portrait')->output(),
                'audit-session-' . $this->session->getKey() . '.pdf',
            )->withMime('application/pdf'),
        ];
    }

    private function report(): array
    {
        $report = app(AuditSessionReportPresenter::class)->build($this->session);

        $report['download_url'] = URL::temporarySignedRoute(
            'audit-sessions.email-report.pdf',
            now('Africa/Nairobi')->addDays(7),
            [
                'auditSession' => $this->session->getKey(),
            ],
        );

        return $report;
    }
}
