<?php

namespace App\Filament\Resources\Accounting\AccountingJournalEntryResource\Pages;

use App\Filament\Resources\Accounting\AccountingJournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditAccountingJournalEntry extends EditRecord
{
    protected static string $resource = AccountingJournalEntryResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('lines');
        $data['lines'] = $this->record->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'debit' => $line->debit,
            'credit' => $line->credit,
            'cost_center_id' => $line->cost_center_id,
            'project_fund_id' => $line->project_fund_id,
            'description' => $line->description,
        ])->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft journal entries can be edited.']);
        }

        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $totalDebit = collect($lines)->sum(fn ($line) => (float) ($line['debit'] ?? 0));
        $totalCredit = collect($lines)->sum(fn ($line) => (float) ($line['credit'] ?? 0));

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw ValidationException::withMessages(['lines' => 'Journal entry is not balanced.']);
        }

        $record->update(array_merge($data, [
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ]));

        $record->lines()->delete();
        foreach ($lines as $line) {
            $record->lines()->create($line);
        }

        return $record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn (): bool => $this->record->status === 'draft')];
    }
}
