<?php

namespace App\Services\Audit;

use App\Mail\HighRiskAuditAlertMail;
use App\Models\AuditLog;
use App\Models\AuditSetting;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class AuditRiskService
{
    public function handle(AuditLog $log): void
    {
        if (! $this->isHighRisk($log)) {
            return;
        }

        if ((bool) AuditSetting::get('send_database_notifications', true)) {
            $this->sendDatabaseNotification($log);
        }

        if ((bool) AuditSetting::get('send_high_risk_alerts', true)) {
            $this->sendEmailAlert($log);
        }
    }

    public function isHighRisk(AuditLog $log): bool
    {
        if (in_array($log->event, [
            'deleted',
            'force_deleted',
            'failed_login',
            'rejected',
            'stock_adjustment',
            'permission_changed',
            'payment_deleted',
            'payment_updated',
            'backdated_transaction',
        ], true)) {
            return true;
        }

        $description = strtolower((string) $log->description);

        foreach ([
            'force deleted',
            'deleted payment',
            'deleted invoice',
            'changed permission',
            'stock adjustment',
            'payment amount',
            'backdated',
            'supplier payment',
            'sales payment',
            'animal status',
            'dead',
            'culled',
            'sold',
        ] as $keyword) {
            if (str_contains($description, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function sendDatabaseNotification(AuditLog $log): void
    {
        try {
            $users = User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Admin', 'Administrator', 'Manager']))
                ->get();
        } catch (\Throwable $e) {
            $users = collect();
        }

        if ($users->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('High-risk audit action detected')
            ->body($log->actor_label . ' performed: ' . $log->event_label . ' - ' . ($log->description ?: $log->record_label))
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->sendToDatabase($users);
    }

    protected function sendEmailAlert(AuditLog $log): void
    {
        $email = AuditSetting::get(
            'high_risk_email',
            AuditSetting::get('default_email', config('mail.from.address'))
        );

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new HighRiskAuditAlertMail($log));
    }
}
