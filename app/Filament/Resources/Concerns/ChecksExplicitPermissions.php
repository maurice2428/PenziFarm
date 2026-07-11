<?php

namespace App\Filament\Resources\Concerns;

trait ChecksExplicitPermissions
{
    protected static function permits(string $permission): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            'Administrator',
            'Admin',
        ]) || $user->can($permission);
    }
}
