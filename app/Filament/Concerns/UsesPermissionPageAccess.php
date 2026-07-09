<?php

namespace App\Filament\Concerns;

trait UsesPermissionPageAccess
{
    protected static function pagePermission(): string
    {
        return property_exists(static::class, 'pagePermission')
            ? (static::$pagePermission ?: '')
            : '';
    }

    public static function canAccess(): bool
    {
        $permission = static::pagePermission();

        if ($permission === '') {
            return false;
        }

        return auth()->user()?->can($permission) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
