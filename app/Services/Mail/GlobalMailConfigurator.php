<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GlobalMailConfigurator
{
    public function apply(bool $fresh = false): bool
    {
        try {
            if (! Schema::hasTable('settings')) {
                return false;
            }

            $settings = $fresh
                ? $this->loadSettings()
                : Cache::remember('global_mail_settings_payload', now()->addMinutes(10), fn () => $this->loadSettings());

            $host = $this->pick($settings, [
                'smtp_host',
                'mail_host',
                'email_host',
                'mail.smtp_host',
                'email.smtp_host',
                'smtp.host',
                'mail.host',
            ]);

            $port = $this->pick($settings, [
                'smtp_port',
                'mail_port',
                'email_port',
                'mail.smtp_port',
                'email.smtp_port',
                'smtp.port',
                'mail.port',
            ], 587);

            $username = $this->pick($settings, [
                'smtp_username',
                'mail_username',
                'email_username',
                'mail.smtp_username',
                'email.smtp_username',
                'smtp.username',
                'mail.username',
            ]);

            $password = $this->pick($settings, [
                'smtp_password',
                'mail_password',
                'email_password',
                'mail.smtp_password',
                'email.smtp_password',
                'smtp.password',
                'mail.password',
            ]);

            $encryption = $this->pick($settings, [
                'smtp_encryption',
                'mail_encryption',
                'email_encryption',
                'mail.smtp_encryption',
                'email.smtp_encryption',
                'smtp.encryption',
                'mail.encryption',
            ], 'tls');

            $fromAddress = $this->pick($settings, [
                'sender_email',
                'from_email',
                'mail_from_address',
                'mail.from_address',
                'email.from_address',
                'mail.from.address',
            ], config('mail.from.address'));

            $fromName = $this->pick($settings, [
                'sender_name',
                'from_name',
                'mail_from_name',
                'mail.from_name',
                'email.from_name',
                'mail.from.name',
            ], config('mail.from.name'));

            if (blank($host) || blank($username) || blank($password) || blank($fromAddress)) {
                logger()->warning('GLOBAL MAIL CONFIGURATION INCOMPLETE', [
                    'host_exists' => filled($host),
                    'username_exists' => filled($username),
                    'password_exists' => filled($password),
                    'from_address_exists' => filled($fromAddress),
                ]);

                return false;
            }

            $password = $this->decryptIfEncrypted($password);
            $encryption = strtolower((string) $encryption);

            if (in_array($encryption, ['', 'none', 'null', 'false', 'no'], true)) {
                $encryption = null;
            }

            Config::set('mail.default', 'smtp');

            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $host,
                'port' => (int) $port,
                'encryption' => $encryption,
                'username' => $username,
                'password' => $password,
                'timeout' => null,
                'local_domain' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
            ]);

            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', $fromName ?: config('app.name'));

            $this->resetMailer();

            return true;
        } catch (\Throwable $e) {
            report($e);

            logger()->error('GLOBAL MAIL CONFIGURATION FAILED', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function clearCache(): void
    {
        Cache::forget('global_mail_settings_payload');
    }

    protected function loadSettings(): array
    {
        $settings = [];

        if (! Schema::hasTable('settings')) {
            return $settings;
        }

        $keyColumn = null;
        $valueColumn = null;

        foreach (['key', 'name', 'setting_key'] as $column) {
            if (Schema::hasColumn('settings', $column)) {
                $keyColumn = $column;
                break;
            }
        }

        foreach (['value', 'setting_value'] as $column) {
            if (Schema::hasColumn('settings', $column)) {
                $valueColumn = $column;
                break;
            }
        }

        if (! $keyColumn || ! $valueColumn) {
            return $settings;
        }

        DB::table('settings')
            ->select([$keyColumn, $valueColumn])
            ->orderBy($keyColumn)
            ->get()
            ->each(function ($row) use (&$settings, $keyColumn, $valueColumn): void {
                $key = $row->{$keyColumn} ?? null;

                if (filled($key)) {
                    $settings[$key] = $row->{$valueColumn} ?? null;
                }
            });

        return $settings;
    }

    protected function pick(array $settings, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $settings) && filled($settings[$key])) {
                return $settings[$key];
            }

            try {
                if (function_exists('setting')) {
                    $value = setting($key);

                    if (filled($value)) {
                        return $value;
                    }
                }
            } catch (\Throwable) {
                //
            }
        }

        return $default;
    }

    protected function decryptIfEncrypted(?string $value): ?string
    {
        if (blank($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    protected function resetMailer(): void
    {
        try {
            $manager = app('mail.manager');

            if (method_exists($manager, 'forgetMailers')) {
                $manager->forgetMailers();
            }
        } catch (\Throwable) {
            //
        }

        try {
            app()->forgetInstance('mailer');
            app()->forgetInstance(\Illuminate\Contracts\Mail\Factory::class);
            app()->forgetInstance(\Illuminate\Contracts\Mail\Mailer::class);
        } catch (\Throwable) {
            //
        }
    }
}
