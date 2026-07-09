<?php

namespace App\Filament\Resources\BreedingBatchResource\Pages;

use App\Filament\Resources\BreedingBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBreedingBatch extends EditRecord
{
    protected static string $resource = BreedingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('delete breeding batches')
                    || auth()->user()?->hasRole('Admin')
                    || auth()->user()?->hasRole('Administrator')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
