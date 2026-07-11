<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\Payroll;
use App\Models\HR\StatutoryRemittance;
use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StatutoryRemittanceService
{
    public function __construct(
        private readonly PayrollStatutoryRateService $rates
    ) {
    }

    public function amountDue(
        Payroll $payroll,
        string $type
    ): float {
        return round($payroll->statutoryDue($type), 2);
    }

    public function amountRemitted(
        Payroll $payroll,
        string $type
    ): float {
        return round(
            (float) StatutoryRemittance::query()
                ->where('payroll_id', $payroll->getKey())
                ->where('statutory_type', $type)
                ->where('status', 'posted')
                ->sum('amount'),
            2
        );
    }

    public function outstanding(
        Payroll $payroll,
        string $type
    ): float {
        return round(
            max(
                0,
                $this->amountDue($payroll, $type)
                - $this->amountRemitted($payroll, $type)
            ),
            2
        );
    }

    public function prepare(
        Payroll $payroll,
        string $type
    ): array {
        $due = $this->amountDue($payroll, $type);
        $outstanding = $this->outstanding($payroll, $type);

        return [
            'period_start' => $payroll->period_start?->toDateString(),
            'period_end' => $payroll->period_end?->toDateString(),
            'due_date' => $this->rates
                ->dueDate($type, $payroll->period_end)
                ->toDateString(),
            'amount_due' => $due,
            'amount' => $outstanding,
        ];
    }

    public function post(
        StatutoryRemittance $remittance
    ): StatutoryRemittance {
        return DB::transaction(function () use (
            $remittance
        ): StatutoryRemittance {
            $locked = StatutoryRemittance::query()
                ->lockForUpdate()
                ->with('payroll')
                ->findOrFail($remittance->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft statutory remittances can be posted.',
                ]);
            }

            if (! $locked->payroll) {
                throw ValidationException::withMessages([
                    'payroll_id' =>
                        'A payroll is required for statutory remittance.',
                ]);
            }

            $outstanding = $this->outstanding(
                $locked->payroll,
                $locked->statutory_type
            );

            $amount = round((float) $locked->amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Remittance amount must be greater than zero.',
                ]);
            }

            if ($amount > $outstanding + 0.01) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Remittance exceeds the outstanding statutory '
                        . 'balance of KES '
                        . number_format($outstanding, 2)
                        . '.',
                ]);
            }

            if (blank($locked->payment_date)) {
                throw ValidationException::withMessages([
                    'payment_date' => 'Payment date and time are required.',
                ]);
            }

            $locked->forceFill([
                'amount_due' =>
                    $this->amountDue(
                        $locked->payroll,
                        $locked->statutory_type
                    ),
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now('Africa/Nairobi'),
            ])->saveQuietly();

            app(AccountingIntegrationPostingService::class)
                ->postModel($locked, 'statutory-remittance-post');

            return $locked->refresh();
        });
    }

    public function reverse(
        StatutoryRemittance $remittance,
        string $reason
    ): StatutoryRemittance {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reversal_reason' =>
                    'A remittance reversal reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $remittance,
            $reason
        ): StatutoryRemittance {
            $locked = StatutoryRemittance::query()
                ->lockForUpdate()
                ->findOrFail($remittance->getKey());

            if (! $locked->isPosted()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only posted statutory remittances can be reversed.',
                ]);
            }

            app(AccountingIntegrationPostingService::class)
                ->reverseSource($locked, $reason);

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now('Africa/Nairobi'),
                'reversal_reason' => $reason,
            ])->saveQuietly();

            return $locked->refresh();
        });
    }
}
