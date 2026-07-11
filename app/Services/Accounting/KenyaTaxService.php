<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingJournalEntry;
use App\Models\Accounting\AccountingTaxSetting;
use App\Models\Accounting\AccountingTaxTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KenyaTaxService
{
    public function setting(
        string $code,
        CarbonInterface|string|null $date = null
    ): AccountingTaxSetting {
        $date = $date
            ? Carbon::parse($date)->toDateString()
            : now('Africa/Nairobi')->toDateString();

        $setting = AccountingTaxSetting::query()
            ->where('code', $code)
            ->effectiveOn($date)
            ->orderByDesc('effective_from')
            ->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'tax_code' => "Tax setting [{$code}] is missing or inactive.",
            ]);
        }

        return $setting;
    }

    /** @return array{net: float, tax: float, gross: float, rate: float, code: string} */
    public function calculateVat(
        float $amount,
        string $taxCode = 'VAT_STANDARD',
        bool $amountIncludesVat = false,
        CarbonInterface|string|null $date = null
    ): array {
        $setting = $this->setting($taxCode, $date);
        $rate = $setting->rateFor();
        $amount = round(max(0, $amount), 2);

        if ($rate <= 0) {
            return [
                'net' => $amount,
                'tax' => 0.0,
                'gross' => $amount,
                'rate' => $rate,
                'code' => $setting->code,
            ];
        }

        if ($amountIncludesVat) {
            $net = round($amount / (1 + ($rate / 100)), 2);
            $tax = round($amount - $net, 2);
            $gross = $amount;
        } else {
            $net = $amount;
            $tax = round($net * ($rate / 100), 2);
            $gross = round($net + $tax, 2);
        }

        return compact('net', 'tax', 'gross', 'rate') + [
            'code' => $setting->code,
        ];
    }

    /** @return array{gross: float, withheld: float, net: float, rate: float, code: string} */
    public function calculateWithholding(
        float $grossAmount,
        string $taxCode,
        string $residency = 'resident',
        CarbonInterface|string|null $date = null
    ): array {
        $setting = $this->setting($taxCode, $date);
        $rate = $setting->rateFor($residency);
        $gross = round(max(0, $grossAmount), 2);
        $withheld = round($gross * ($rate / 100), 2);

        return [
            'gross' => $gross,
            'withheld' => $withheld,
            'net' => round($gross - $withheld, 2),
            'rate' => $rate,
            'code' => $setting->code,
        ];
    }

    public function vatDueDate(
        CarbonInterface|string $transactionDate
    ): Carbon {
        return Carbon::parse($transactionDate, 'Africa/Nairobi')
            ->addMonthNoOverflow()
            ->startOfMonth()
            ->day(20);
    }

    public function withholdingDueDate(
        CarbonInterface|string $transactionDate,
        int $workingDays = 5
    ): Carbon {
        $date = Carbon::parse($transactionDate, 'Africa/Nairobi');
        $added = 0;

        while ($added < $workingDays) {
            $date->addDay();

            if (! $date->isWeekend()) {
                $added++;
            }
        }

        return $date;
    }

    public function recordTransaction(array $data): AccountingTaxTransaction
    {
        $setting = filled($data['tax_setting_id'] ?? null)
            ? AccountingTaxSetting::query()->findOrFail($data['tax_setting_id'])
            : $this->setting(
                $data['tax_code'],
                $data['transaction_date'] ?? null
            );

        $transactionDate = Carbon::parse(
            $data['transaction_date'] ?? now('Africa/Nairobi')
        );

        $dueDate = $data['due_date'] ?? match ($setting->type) {
            'vat' => $this->vatDueDate($transactionDate)->toDateString(),
            'withholding' => $this->withholdingDueDate(
                $transactionDate,
                (int) ($setting->remittance_due_days ?? 5)
            )->toDateString(),
            default => null,
        };

        return AccountingTaxTransaction::query()->create([
            'tax_number' =>
                $data['tax_number']
                ?? 'TAX'
                . now('Africa/Nairobi')->format('Ymd')
                . strtoupper(Str::random(8)),
            'tax_setting_id' => $setting->id,
            'journal_entry_id' => $data['journal_entry_id'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'transaction_date' => $transactionDate->toDateString(),
            'tax_point_date' =>
                $data['tax_point_date']
                ?? $transactionDate->toDateString(),
            'due_date' => $dueDate,
            'direction' => $data['direction'],
            'tax_code' => $setting->code,
            'tax_rate' =>
                $data['tax_rate']
                ?? $setting->rateFor($data['residency'] ?? 'resident'),
            'taxable_amount' => round((float) ($data['taxable_amount'] ?? 0), 2),
            'tax_amount' => round((float) ($data['tax_amount'] ?? 0), 2),
            'gross_amount' => round((float) ($data['gross_amount'] ?? 0), 2),
            'status' => $data['status'] ?? 'posted',
            'party_name' => $data['party_name'] ?? null,
            'party_pin' => $data['party_pin'] ?? null,
            'certificate_number' => $data['certificate_number'] ?? null,
            'etims_invoice_number' => $data['etims_invoice_number'] ?? null,
            'etims_control_unit' => $data['etims_control_unit'] ?? null,
            'etims_internal_data' => $data['etims_internal_data'] ?? null,
            'created_by' => $data['created_by'] ?? Auth::id(),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function registerJournalTax(
        AccountingJournalEntry $journal,
        array $taxData
    ): AccountingTaxTransaction {
        return $this->recordTransaction(array_merge($taxData, [
            'journal_entry_id' => $journal->id,
            'source_type' => $journal->source_type,
            'source_id' => $journal->source_id,
            'transaction_date' => $journal->transaction_date,
        ]));
    }
}
