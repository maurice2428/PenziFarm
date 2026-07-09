<?php

namespace App\Mail;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HighRiskAuditAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AuditLog $auditLog
    ) {}

    public function build(): self
    {
        return $this
            ->subject('High-Risk Audit Alert - ' . $this->auditLog->event_label)
            ->markdown('emails.audit.high-risk-alert', [
                'log' => $this->auditLog,
            ]);
    }
}
