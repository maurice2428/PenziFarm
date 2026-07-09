<?php

namespace App\Filament\Resources\Sales\SalesPaymentResource\Pages;

use App\Filament\Resources\Sales\SalesPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesPayment extends EditRecord
{
    protected static string $resource = SalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()?->can('view sales payments') ?? false),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete sales payments') ?? false),
        ];
    }
}
