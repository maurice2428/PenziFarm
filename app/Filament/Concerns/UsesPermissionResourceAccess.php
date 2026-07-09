<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait UsesPermissionResourceAccess
{
    protected static function permissionGroup(): string
    {
        return property_exists(static::class, 'permissionGroup')
            ? (static::$permissionGroup ?: '')
            : '';
    }

    protected static function canDo(string $action): bool
    {
        $group = static::permissionGroup();

        if ($group === '') {
            return false;
        }

        return auth()->user()?->can($action . ' ' . $group) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::canDo('view');
    }

    public static function canView(Model $record): bool
    {
        return static::canDo('view');
    }

    public static function canCreate(): bool
    {
        return static::canDo('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canDo('edit') || static::canDo('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canDo('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::canDo('delete');
    }

    public static function canRestore(Model $record): bool
    {
        return static::canDo('restore');
    }

    public static function canRestoreAny(): bool
    {
        return static::canDo('restore');
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canDo('force delete');
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canDo('force delete');
    }
}
