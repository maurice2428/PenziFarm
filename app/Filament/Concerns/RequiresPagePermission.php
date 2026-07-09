<?php

namespace App\Filament\Concerns;

trait RequiresPagePermission
{
    protected static ?string $pagePermission = null;

    public static function canAccess(): bool
    {
        if (blank(static::$pagePermission)) {
            return false;
        }

        return auth()->user()?->can(static::$pagePermission) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
