<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Services\Procurement\ProcurementLifecycleService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource =
        PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancelOrder')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(
                    fn (): bool =>
                        $this->record
                            ->canBeCancelledSafely()
                        && (
                            auth()->user()?->can(
                                'cancel purchase orders'
                            )
                            || auth()->user()?->can(
                                'delete purchase orders'
                            )
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                        )
                )
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make(
                        'cancellation_reason'
                    )
                        ->required()
                        ->minLength(8)
                        ->rows(3),
                ])
                ->action(
                    function (array $data): void {
                        app(
                            ProcurementLifecycleService::class
                        )->cancelPurchaseOrder(
                            $this->record,
                            $data['cancellation_reason']
                        );

                        Notification::make()
                            ->success()
                            ->title(
                                'Purchase order cancelled'
                            )
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
