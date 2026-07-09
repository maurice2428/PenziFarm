<?php

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class GlobalMailService
{
    public function sendRaw(string $to, string $subject, string $body): bool
    {
        try {
            $configured = app(GlobalMailConfigurator::class)->apply();

            if (! $configured) {
                return false;
            }

            Mail::raw($body, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            report($e);

            logger()->error('GLOBAL RAW MAIL FAILED', [
                'to' => $to,
                'subject' => $subject,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendMailable(string $to, Mailable $mailable): bool
    {
        try {
            $configured = app(GlobalMailConfigurator::class)->apply();

            if (! $configured) {
                return false;
            }

            Mail::to($to)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            report($e);

            logger()->error('GLOBAL MAILABLE FAILED', [
                'to' => $to,
                'mailable' => $mailable::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
