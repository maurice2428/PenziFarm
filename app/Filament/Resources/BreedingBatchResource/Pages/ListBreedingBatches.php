<?php

namespace App\Filament\Resources\BreedingBatchResource\Pages;

use App\Filament\Resources\BreedingBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBreedingBatches extends ListRecords
{
    protected static string $resource = BreedingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Breeding Batch')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => auth()->user()?->can('create breeding batches')
                    || auth()->user()?->hasRole('Admin')
                    || auth()->user()?->hasRole('Administrator')
                ),
        ];
    }
}
