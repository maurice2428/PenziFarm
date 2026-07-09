<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.auth.login';

    public function getTitle(): string
    {
        return setting('farm.name', 'Lelekwe Farm(s)') . ' Login';
    }

    public function getHeading(): string
    {
        return 'Welcome back';
    }

    public function getSubheading(): ?string
    {
        return 'Sign in to continue to the ERP dashboard.';
    }
}
