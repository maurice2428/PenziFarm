<?php

namespace App\Filament\Resources\BreedingBatchResource\Pages;

use App\Filament\Resources\BreedingBatchResource;
use App\Models\BreedingBatch;
use App\Services\Breeding\BreedingBatchLifecycleService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBreedingBatch extends EditRecord
{
    protected static string $resource =
        BreedingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageLifecycle')
                ->label('Archive / Delete')
                ->icon('heroicon-o-archive-box')
                ->color('danger')
                ->visible(
                    fn (): bool =>
                        ! $this->getRecord()->trashed()
                        && BreedingBatchResource::canDelete(
                            $this->getRecord()
                        )
                )
                ->modalWidth('2xl')
                ->modalHeading(
                    fn (): string =>
                        'Archive or permanently delete '
                        . $this->getRecord()->batch_number
                )
                ->modalDescription(
                    fn () =>
                        BreedingBatchResource::lifecycleSummaryHtml(
                            $this->getRecord()
                        )
                )
                ->form([
                    Forms\Components\Radio::make('disposition')
                        ->label('What should happen to this batch?')
                        ->options([
                            'archive' =>
                                'Archive batch and all breeding outcomes',
                            'permanent_delete' =>
                                'Permanently delete this empty batch',
                        ])
                        ->descriptions([
                            'archive' =>
                                'Recommended. Preserves the complete '
                                . 'history and allows restoration.',
                            'permanent_delete' =>
                                'Only available when there is no delivery '
                                . 'or registered offspring history.',
                        ])
                        ->default('archive')
                        ->live()
                        ->required(),

                    Forms\Components\Textarea::make('reason')
                        ->label('Archive reason')
                        ->rows(3)
                        ->required(
                            fn (Get $get): bool =>
                                $get('disposition') === 'archive'
                        )
                        ->visible(
                            fn (Get $get): bool =>
                                $get('disposition') === 'archive'
                        ),
                ])
                ->action(function (array $data): void {
                    /** @var BreedingBatch $record */
                    $record = $this->getRecord();

                    $service = app(
                        BreedingBatchLifecycleService::class
                    );

                    if (
                        ($data['disposition'] ?? 'archive')
                        === 'permanent_delete'
                    ) {
                        $service->permanentlyDelete($record);

                        Notification::make()
                            ->title(
                                'Breeding batch permanently deleted'
                            )
                            ->success()
                            ->send();
                    } else {
                        $service->archive(
                            $record,
                            $data['reason'] ?? null
                        );

                        Notification::make()
                            ->title('Breeding batch archived')
                            ->body(
                                'All related breeding outcomes are hidden '
                                . 'and can be restored from the list page.'
                            )
                            ->success()
                            ->send();
                    }

                    $this->redirect(
                        static::getResource()::getUrl('index')
                    );
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
