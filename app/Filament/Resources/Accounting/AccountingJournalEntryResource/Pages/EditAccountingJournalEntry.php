<?php

namespace App\Filament\Resources\Accounting\AccountingJournalEntryResource\Pages;

use App\Filament\Resources\Accounting\AccountingJournalEntryResource;
use App\Services\Accounting\AccountingService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAccountingJournalEntry extends EditRecord
{
    protected static string $resource =
        AccountingJournalEntryResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('lines');

        $data['lines'] = $this->record->lines
            ->map(fn ($line): array => [
                'account_id' => $line->account_id,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'cost_center_id' => $line->cost_center_id,
                'project_fund_id' => $line->project_fund_id,
                'description' => $line->description,
                'party_type' => $line->party_type,
                'party_id' => $line->party_id,
                'party_name' => $line->party_name,
                'party_pin' => $line->party_pin,
                'tax_code' => $line->tax_code,
                'tax_rate' => $line->tax_rate,
                'tax_amount' => $line->tax_amount,
                'etims_document_number' =>
                    $line->etims_document_number,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        return app(AccountingService::class)
            ->updateDraftJournal($record, $data, $lines);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        $this->record->canBeDeletedSafely()
                )
                ->using(function (): void {
                    app(AccountingService::class)
                        ->deleteDraftJournal($this->record);
                }),
        ];
    }
}
