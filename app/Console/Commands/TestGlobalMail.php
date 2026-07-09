<?php

namespace App\Console\Commands;

use App\Services\Mail\GlobalMailConfigurator;
use App\Services\Mail\GlobalMailService;
use Illuminate\Console\Command;

class TestGlobalMail extends Command
{
    protected $signature = 'mail:test-global {--to= : Recipient email address}';

    protected $description = 'Test ERP global email settings from the database.';

    public function handle(): int
    {
        $to = $this->option('to') ?: 'mauricenzioki2428@gmail.com';

        app(GlobalMailConfigurator::class)->clearCache();

        $configured = app(GlobalMailConfigurator::class)->apply(fresh: true);

        if (! $configured) {
            $this->error('Global mail settings could not be applied. Check the Email Settings page values.');

            return self::FAILURE;
        }

        $sent = app(GlobalMailService::class)->sendRaw(
            to: $to,
            subject: 'Lelekwe ERP Global Mail Test',
            body: 'This is a test email sent using the ERP Global Email Settings.'
        );

        if (! $sent) {
            $this->error('Email failed. Check storage/logs/laravel.log for GLOBAL RAW MAIL FAILED.');

            return self::FAILURE;
        }

        $this->info("Test email sent successfully to {$to}");

        return self::SUCCESS;
    }
}
