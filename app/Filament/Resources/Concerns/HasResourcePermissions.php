<?php

namespace App\Filament\Resources\Concerns;

trait HasResourcePermissions
{
    protected static function permissionPrefix(): string
    {
        return static::$permissionPrefix;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view ' . static::permissionPrefix()) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view ' . static::permissionPrefix()) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ' . static::permissionPrefix()) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create ' . static::permissionPrefix()) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit ' . static::permissionPrefix()) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete ' . static::permissionPrefix()) ?? false;
    }
}
