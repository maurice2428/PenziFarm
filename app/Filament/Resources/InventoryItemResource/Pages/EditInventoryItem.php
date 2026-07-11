<?php

namespace App\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\Resources\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryItem extends EditRecord
{
    protected static string $resource =
        InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleActive')
                ->label(
                    $this->record->is_active
                        ? 'Deactivate'
                        : 'Activate'
                )
                ->icon(
                    $this->record->is_active
                        ? 'heroicon-o-pause-circle'
                        : 'heroicon-o-play-circle'
                )
                ->color(
                    $this->record->is_active
                        ? 'warning'
                        : 'success'
                )
                ->action(
                    fn () =>
                        $this->record->update([
                            'is_active' =>
                                ! $this->record->is_active,
                        ])
                ),

            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        static::getResource()::canDelete(
                            $this->record
                        )
                ),
        ];
    }
}
