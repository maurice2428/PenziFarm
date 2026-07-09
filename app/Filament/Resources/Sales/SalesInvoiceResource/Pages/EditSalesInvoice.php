<?php

namespace App\Filament\Resources\Sales\SalesInvoiceResource\Pages;

use App\Filament\Resources\Sales\SalesInvoiceResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditSalesInvoice extends EditRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    /*  protected function afterSave(): void
      {
          $this->record->recalculateTotals();
      }*/
    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
        $this->record->refresh();
        $this->record->markAnimalsAsSold();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye')
                ->visible(fn() => auth()->user()?->can('view sales invoices') ?? false),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn() => auth()->user()?->can('delete sales invoices') ?? false),
        ];
    }
}
