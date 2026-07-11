<?php

namespace App\Filament\Resources\PurchaseOrderPaymentResource\Pages;

use App\Filament\Resources\PurchaseOrderPaymentResource;
use App\Services\Procurement\ProcurementLifecycleService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrderPayment extends EditRecord
{
    protected static string $resource =
        PurchaseOrderPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reverse')
                ->label('Reverse Payment')
                ->icon(
                    'heroicon-o-arrow-uturn-left'
                )
                ->color('danger')
                ->visible(
                    fn (): bool =>
                        $this->record->can_be_reversed
                )
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make(
                        'reversal_reason'
                    )
                        ->required()
                        ->minLength(8)
                        ->rows(3),
                ])
                ->action(
                    function (array $data): void {
                        app(
                            ProcurementLifecycleService::class
                        )->reversePayment(
                            $this->record,
                            $data['reversal_reason']
                        );

                        Notification::make()
                            ->success()
                            ->title('Payment reversed')
                            ->send();

                        $this->redirect(
                            static::getResource()::getUrl(
                                'index'
                            )
                        );
                    }
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
