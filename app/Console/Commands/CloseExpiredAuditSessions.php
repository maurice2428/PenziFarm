<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditSessionService;
use Illuminate\Console\Command;

class CloseExpiredAuditSessions extends Command
{
    protected $signature = 'audit:sessions-close-expired';

    protected $description = 'Close expired audit sessions and optionally send audit summary emails.';

    public function handle(): int
    {
        $count = app(AuditSessionService::class)->closeExpiredSessions();

        $this->info("Closed {$count} expired audit session(s).");

        return self::SUCCESS;
    }
}
