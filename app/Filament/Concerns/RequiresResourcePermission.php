<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait RequiresResourcePermission
{
    protected static ?string $permissionKey = null;

    protected static function permissionKey(): string
    {
        return static::$permissionKey ?? '';
    }

    protected static function allowsPermission(string $action): bool
    {
        $permissionKey = static::permissionKey();

        if ($permissionKey === '') {
            return false;
        }

        return auth()->user()?->can($action . ' ' . $permissionKey) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::allowsPermission('view');
    }

    public static function canView(Model $record): bool
    {
        return static::allowsPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::allowsPermission('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowsPermission('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowsPermission('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::allowsPermission('delete');
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }
}
