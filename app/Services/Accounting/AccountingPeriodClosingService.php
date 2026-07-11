<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingFiscalYear;
use App\Models\Accounting\AccountingPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodClosingService
{
    public function closePeriod(AccountingPeriod $period): AccountingPeriod
    {
        return DB::transaction(function () use ($period): AccountingPeriod {
            $locked = AccountingPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            $drafts = $locked->journalEntries()
                ->where('status', 'draft')
                ->count();

            if ($drafts > 0) {
                throw ValidationException::withMessages([
                    'period' =>
                        "This period has {$drafts} draft journal(s). Post or delete them first.",
                ]);
            }

            $locked->forceFill([
                'status' => 'closed',
                'closed_by' => Auth::id(),
                'closed_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }

    public function lockPeriod(AccountingPeriod $period): AccountingPeriod
    {
        $closed = $period->status === 'closed'
            ? $period
            : $this->closePeriod($period);

        $closed->forceFill(['status' => 'locked'])->save();

        return $closed->refresh();
    }

    public function reopenPeriod(AccountingPeriod $period): AccountingPeriod
    {
        $period->forceFill([
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ])->save();

        return $period->refresh();
    }

    public function closeFiscalYear(
        AccountingFiscalYear $year
    ): AccountingFiscalYear {
        return DB::transaction(function () use ($year): AccountingFiscalYear {
            $locked = AccountingFiscalYear::query()
                ->lockForUpdate()
                ->with('periods')
                ->findOrFail($year->id);

            $openPeriods = $locked->periods
                ->where('status', 'open')
                ->count();

            if ($openPeriods > 0) {
                throw ValidationException::withMessages([
                    'fiscal_year' =>
                        "Close all {$openPeriods} open period(s) first.",
                ]);
            }

            $drafts = $locked->journalEntries()
                ->where('status', 'draft')
                ->count();

            if ($drafts > 0) {
                throw ValidationException::withMessages([
                    'fiscal_year' =>
                        "The fiscal year has {$drafts} draft journal(s).",
                ]);
            }

            $locked->forceFill([
                'status' => 'closed',
                'closed_by' => Auth::id(),
                'closed_at' => now(),
                'is_current' => false,
            ])->save();

            return $locked->refresh();
        });
    }

    public function lockFiscalYear(
        AccountingFiscalYear $year
    ): AccountingFiscalYear {
        $closed = $year->status === 'closed'
            ? $year
            : $this->closeFiscalYear($year);

        $closed->forceFill(['status' => 'locked'])->save();

        return $closed->refresh();
    }

    public function reopenFiscalYear(
        AccountingFiscalYear $year
    ): AccountingFiscalYear {
        $year->forceFill([
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ])->save();

        return $year->refresh();
    }
}
