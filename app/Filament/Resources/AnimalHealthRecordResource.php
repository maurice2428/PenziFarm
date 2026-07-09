<?php

namespace App\Filament\Resources;

use App\Models\AnimalHealthRecord;
use Filament\Resources\Resource;

class AnimalHealthRecordResource extends Resource
{
    protected static ?string $model = AnimalHealthRecord::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [];
    }
}
