<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\Payroll;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollPayment;
use App\Models\HR\PayrollPaymentItem;
use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollPaymentService
{
    public function createDraftForPayroll(
        Payroll $payroll
    ): PayrollPayment {
        return DB::transaction(function () use ($payroll): PayrollPayment {
            $lockedPayroll = Payroll::query()
                ->lockForUpdate()
                ->findOrFail($payroll->getKey());

            if (! $lockedPayroll->canReceivePayments()) {
                throw ValidationException::withMessages([
                    'payroll_id' =>
                        'Only approved or posted payrolls with an '
                        . 'outstanding balance can be paid.',
                ]);
            }

            $existing = PayrollPayment::query()
                ->where('payroll_id', $lockedPayroll->getKey())
                ->where('status', 'draft')
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing->load('items.employee');
            }

            $payment = PayrollPayment::query()->create([
                'payroll_id' => $lockedPayroll->getKey(),
                'payment_date' => now('Africa/Nairobi'),
                'status' => 'draft',
                'total_amount' =>
                    $this->outstandingForPayroll($lockedPayroll),
                'created_by' => auth()->id(),
                'notes' =>
                    'Salary payment draft for '
                    . $lockedPayroll->month
                    . '/'
                    . $lockedPayroll->year,
            ]);

            $this->populateOutstandingItems($payment);

            return $payment->refresh()->load('items.employee');
        });
    }

    public function outstandingForPayroll(
        Payroll $payroll
    ): float {
        $payroll->loadMissing('items');

        return round(
            (float) $payroll->items->sum(
                fn (PayrollItem $item): float =>
                    $this->itemOutstanding($item)
            ),
            2
        );
    }

    public function payrollSummary(
        Payroll $payroll
    ): array {
        $payroll->loadMissing([
            'items.employee',
        ]);

        $employeeDeductions =
            (float) $payroll->total_paye
            + (float) $payroll->total_nssf_employee
            + (float) $payroll->total_shif
            + (float) $payroll
                ->total_housing_levy_employee
            + (float) $payroll
                ->total_salary_advance_deductions
            + (float) $payroll
                ->total_other_deductions;

        $employerStatutories =
            (float) $payroll->total_nssf_employer
            + (float) $payroll
                ->total_housing_levy_employer;

        return [
            'payroll_period_display' =>
                optional($payroll->period_start)
                    ->format('d M Y')
                . ' – '
                . optional($payroll->period_end)
                    ->format('d M Y'),

            'payroll_employee_count' =>
                $payroll->items->count(),

            'payroll_gross_total' =>
                round(
                    (float) $payroll->total_gross,
                    2
                ),

            'payroll_employee_deductions' =>
                round($employeeDeductions, 2),

            'payroll_net_total' =>
                round(
                    (float) $payroll->total_net,
                    2
                ),

            'payroll_paid_total' =>
                round(
                    (float) $payroll->total_paid,
                    2
                ),

            'payroll_balance_total' =>
                $this->outstandingForPayroll(
                    $payroll
                ),

            'payroll_employer_statutories' =>
                round($employerStatutories, 2),

            'payroll_employer_cost' =>
                round(
                    (float) $payroll
                        ->total_employer_cost,
                    2
                ),
        ];
    }

    public function formRowsForPayroll(
        Payroll $payroll,
        ?int $excludePaymentId = null
    ): array {
        $payroll->loadMissing([
            'items.employee.department',
            'items.employee.jobTitle',
        ]);

        return $payroll->items
            ->map(
                fn (
                    PayrollItem $item
                ): array => $this->formRowForItem(
                    $item,
                    $excludePaymentId
                )
            )
            ->filter(
                fn (array $row): bool =>
                    (float) $row[
                        'outstanding_amount'
                    ] > 0
            )
            ->values()
            ->all();
    }

    public function ensureDraftItems(
        PayrollPayment $payment
    ): PayrollPayment {
        if (! $payment->isDraft()) {
            return $payment->load([
                'items.payrollItem.employee.department',
                'items.payrollItem.employee.jobTitle',
                'items.employee',
                'payroll.items',
            ]);
        }

        return DB::transaction(function () use (
            $payment
        ): PayrollPayment {
            $locked = PayrollPayment::query()
                ->lockForUpdate()
                ->with([
                    'payroll.items.employee',
                    'items',
                ])
                ->findOrFail(
                    $payment->getKey()
                );

            if ($locked->items->isEmpty()) {
                foreach (
                    $this->formRowsForPayroll(
                        $locked->payroll
                    ) as $row
                ) {
                    $locked->items()->create(
                        $this->persistableRow(
                            $row
                        )
                    );
                }

                $this->refreshDraftTotal(
                    $locked
                );
            }

            return $locked->refresh()->load([
                'items.payrollItem.employee.department',
                'items.payrollItem.employee.jobTitle',
                'items.employee',
                'payroll.items',
            ]);
        });
    }

    public function syncDraftItemsFromForm(
        PayrollPayment $payment,
        array $rows
    ): PayrollPayment {
        return DB::transaction(function () use (
            $payment,
            $rows
        ): PayrollPayment {
            $locked = PayrollPayment::query()
                ->lockForUpdate()
                ->with([
                    'payroll.items',
                    'items',
                ])
                ->findOrFail(
                    $payment->getKey()
                );

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Only draft salary payments can be edited.',
                ]);
            }

            $validItems = $locked->payroll
                ->items
                ->keyBy(
                    fn (PayrollItem $item): int =>
                        (int) $item->getKey()
                );

            $keptIds = [];
            $seenPayrollItems = [];

            foreach ($rows as $index => $row) {
                $payrollItemId = (int) (
                    $row['payroll_item_id'] ?? 0
                );

                if (
                    $payrollItemId <= 0
                    || ! $validItems->has(
                        $payrollItemId
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.payroll_item_id" =>
                            'This employee line does not belong '
                            . 'to the selected payroll.',
                    ]);
                }

                if (
                    in_array(
                        $payrollItemId,
                        $seenPayrollItems,
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.payroll_item_id" =>
                            'The employee appears more than once '
                            . 'in this salary payment.',
                    ]);
                }

                $seenPayrollItems[] =
                    $payrollItemId;

                /** @var PayrollItem $payrollItem */
                $payrollItem = $validItems->get(
                    $payrollItemId
                );

                $amount = round(
                    (float) (
                        $row['amount'] ?? 0
                    ),
                    2
                );

                $maximum = $this->itemOutstanding(
                    $payrollItem,
                    $locked->getKey()
                );

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.amount" =>
                            'Amount to Pay must be greater '
                            . 'than zero.',
                    ]);
                }

                if ($amount > $maximum + 0.01) {
                    throw ValidationException::withMessages([
                        "items.{$index}.amount" =>
                            'Amount to Pay exceeds the employee '
                            . 'outstanding salary of KES '
                            . number_format(
                                $maximum,
                                2
                            )
                            . '.',
                    ]);
                }

                $method = (string) (
                    $row['payment_method']
                    ?? 'bank'
                );

                if (
                    ! in_array(
                        $method,
                        [
                            'bank',
                            'mpesa',
                            'airtel_money',
                            'cash',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.payment_method" =>
                            'Select a valid payment method.',
                    ]);
                }

                if (
                    $method === 'bank'
                    && blank(
                        $row[
                            'bank_account_number'
                        ] ?? null
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.bank_account_number" =>
                            'Bank account number is required '
                            . 'for bank payments.',
                    ]);
                }

                if (
                    in_array(
                        $method,
                        [
                            'mpesa',
                            'airtel_money',
                        ],
                        true
                    )
                    && blank(
                        $row['phone_number']
                        ?? null
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.phone_number" =>
                            'Mobile money phone number is required.',
                    ]);
                }

                $attributes = [
                    'payroll_payment_id' =>
                        $locked->getKey(),

                    'payroll_item_id' =>
                        $payrollItemId,
                ];

                $values = $this->persistableRow([
                    ...$row,
                    'payroll_item_id' =>
                        $payrollItemId,
                    'employee_id' =>
                        $payrollItem
                            ->employee_id,
                    'amount' => $amount,
                    'payment_method' =>
                        $method,
                    'status' => 'draft',
                ]);

                $item = PayrollPaymentItem::query()
                    ->updateOrCreate(
                        $attributes,
                        $values
                    );

                $keptIds[] = $item->getKey();
            }

            $locked->items()
                ->when(
                    $keptIds !== [],
                    fn ($query) =>
                        $query->whereNotIn(
                            'id',
                            $keptIds
                        )
                )
                ->when(
                    $keptIds === [],
                    fn ($query) => $query
                )
                ->delete();

            $this->refreshDraftTotal(
                $locked
            );

            return $locked->refresh()->load([
                'items.payrollItem.employee.department',
                'items.payrollItem.employee.jobTitle',
                'items.employee',
                'payroll',
            ]);
        });
    }

    private function persistableRow(
        array $row
    ): array {
        return [
            'payroll_item_id' =>
                (int) $row[
                    'payroll_item_id'
                ],

            'employee_id' =>
                (int) $row['employee_id'],

            'amount' =>
                round(
                    (float) $row['amount'],
                    2
                ),

            'payment_method' =>
                (string) (
                    $row['payment_method']
                    ?? 'bank'
                ),

            'phone_number' =>
                filled(
                    $row['phone_number']
                    ?? null
                )
                    ? trim(
                        (string) $row[
                            'phone_number'
                        ]
                    )
                    : null,

            'bank_name' =>
                filled(
                    $row['bank_name']
                    ?? null
                )
                    ? trim(
                        (string) $row[
                            'bank_name'
                        ]
                    )
                    : null,

            'bank_account_number' =>
                filled(
                    $row[
                        'bank_account_number'
                    ] ?? null
                )
                    ? trim(
                        (string) $row[
                            'bank_account_number'
                        ]
                    )
                    : null,

            'transaction_reference' =>
                filled(
                    $row[
                        'transaction_reference'
                    ] ?? null
                )
                    ? trim(
                        (string) $row[
                            'transaction_reference'
                        ]
                    )
                    : null,

            'status' =>
                (string) (
                    $row['status']
                    ?? 'draft'
                ),

            'notes' =>
                filled(
                    $row['notes'] ?? null
                )
                    ? trim(
                        (string) $row['notes']
                    )
                    : null,
        ];
    }

    public function formRowsForPayment(
        PayrollPayment $payment
    ): array {
        $payment->loadMissing([
            'items.payrollItem.employee.department',
            'items.payrollItem.employee.jobTitle',
            'items.employee',
        ]);

        return $payment->items
            ->map(function (
                PayrollPaymentItem $paymentItem
            ) use ($payment): array {
                $payrollItem =
                    $paymentItem->payrollItem;

                if (
                    ! $payrollItem
                    && $paymentItem->employee_id
                    && $payment->payroll_id
                ) {
                    $payrollItem = PayrollItem::query()
                        ->where(
                            'payroll_id',
                            $payment->payroll_id
                        )
                        ->where(
                            'employee_id',
                            $paymentItem->employee_id
                        )
                        ->first();

                    if (
                        $payrollItem
                        && $payment->isDraft()
                    ) {
                        $paymentItem->forceFill([
                            'payroll_item_id' =>
                                $payrollItem
                                    ->getKey(),
                        ])->saveQuietly();
                    }
                }

                if (! $payrollItem) {
                    return [
                        'id' => $paymentItem->getKey(),
                        'payroll_item_id' =>
                            $paymentItem
                                ->payroll_item_id,
                        'employee_id' =>
                            $paymentItem->employee_id,
                        'employee_name' =>
                            $paymentItem
                                ->employee
                                ?->full_name
                            ?? 'Employee',
                        'employee_number' =>
                            $paymentItem
                                ->employee
                                ?->employee_number
                            ?? '—',
                        'department_name' => '—',
                        'job_title_name' => '—',
                        'basic_salary' => 0,
                        'allowances_total' => 0,
                        'gross_pay' => 0,
                        'taxable_pay' => 0,
                        'paye' => 0,
                        'nssf' => 0,
                        'sha' => 0,
                        'housing_levy' => 0,
                        'salary_advance_deduction' => 0,
                        'other_deductions' => 0,
                        'deductions_total' => 0,
                        'net_pay' =>
                            (float) $paymentItem->amount,
                        'already_paid' => 0,
                        'outstanding_amount' =>
                            (float) $paymentItem->amount,
                        'amount' =>
                            (float) $paymentItem->amount,
                        'payment_method' =>
                            $paymentItem
                                ->payment_method,
                        'phone_number' =>
                            $paymentItem
                                ->phone_number,
                        'bank_name' =>
                            $paymentItem->bank_name,
                        'bank_account_number' =>
                            $paymentItem
                                ->bank_account_number,
                        'transaction_reference' =>
                            $paymentItem
                                ->transaction_reference,
                        'status' =>
                            $paymentItem->status,
                        'notes' =>
                            $paymentItem->notes,
                    ];
                }

                $row = $this->formRowForItem(
                    $payrollItem,
                    $payment->getKey()
                );

                $row['id'] =
                    $paymentItem->getKey();

                $row['amount'] =
                    (float) $paymentItem->amount;

                $row['payment_method'] =
                    $paymentItem->payment_method;

                $row['phone_number'] =
                    $paymentItem->phone_number;

                $row['bank_name'] =
                    $paymentItem->bank_name;

                $row['bank_account_number'] =
                    $paymentItem
                        ->bank_account_number;

                $row['transaction_reference'] =
                    $paymentItem
                        ->transaction_reference;

                $row['status'] =
                    $paymentItem->status;

                $row['notes'] =
                    $paymentItem->notes;

                return $row;
            })
            ->values()
            ->all();
    }

    private function formRowForItem(
        PayrollItem $item,
        ?int $excludePaymentId = null
    ): array {
        $item->loadMissing([
            'employee.department',
            'employee.jobTitle',
        ]);

        $employee = $item->employee;

        $outstanding = $this->itemOutstanding(
            $item,
            $excludePaymentId
        );

        $alreadyPaid = max(
            0,
            (float) $item->net_pay
            - $outstanding
        );

        $deductions =
            (float) $item->paye
            + (float) $item->nssf
            + (float) $item->sha
            + (float) $item->housing_levy
            + (float) $item
                ->salary_advance_deduction
            + (float) $item->other_deductions;

        $method = in_array(
            $employee?->payment_method,
            [
                'bank',
                'mpesa',
                'airtel_money',
                'cash',
            ],
            true
        )
            ? $employee->payment_method
            : 'bank';

        return [
            'payroll_item_id' =>
                $item->getKey(),

            'employee_id' =>
                $item->employee_id,

            'employee_name' =>
                $employee?->full_name
                ?: 'Employee',

            'employee_number' =>
                $employee?->employee_number
                ?: '—',

            'department_name' =>
                $employee?->department?->name
                ?: '—',

            'job_title_name' =>
                $employee?->jobTitle?->name
                ?: '—',

            'basic_salary' =>
                (float) $item->basic_salary,

            'allowances_total' =>
                (float) $item
                    ->allowances_total,

            'gross_pay' =>
                (float) $item->gross_pay,

            'taxable_pay' =>
                (float) $item->taxable_pay,

            'paye' =>
                (float) $item->paye,

            'nssf' =>
                (float) $item->nssf,

            'sha' =>
                (float) $item->sha,

            'housing_levy' =>
                (float) $item->housing_levy,

            'salary_advance_deduction' =>
                (float) $item
                    ->salary_advance_deduction,

            'other_deductions' =>
                (float) $item
                    ->other_deductions,

            'deductions_total' =>
                round($deductions, 2),

            'net_pay' =>
                (float) $item->net_pay,

            'already_paid' =>
                round($alreadyPaid, 2),

            'outstanding_amount' =>
                round($outstanding, 2),

            'amount' =>
                round($outstanding, 2),

            'payment_method' => $method,

            'phone_number' =>
                $method === 'mpesa'
                    ? $employee?->mpesa_number
                    : (
                        $method === 'airtel_money'
                            ? $employee
                                ?->airtel_money_number
                            : null
                    ),

            'bank_name' =>
                $method === 'bank'
                    ? $employee?->bank_name
                    : null,

            'bank_account_number' =>
                $method === 'bank'
                    ? $employee?->account_number
                    : null,

            'transaction_reference' => null,
            'status' => 'draft',
            'notes' => null,
        ];
    }

    public function populateOutstandingItems(
        PayrollPayment $payment
    ): PayrollPayment {
        return DB::transaction(function () use ($payment): PayrollPayment {
            $locked = PayrollPayment::query()
                ->lockForUpdate()
                ->with('payroll.items.employee')
                ->findOrFail($payment->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft salary payments can be rebuilt.',
                ]);
            }

            $locked->items()->delete();

            foreach (
                $this->formRowsForPayroll(
                    $locked->payroll
                ) as $row
            ) {
                $locked->items()->create([
                    'payroll_item_id' =>
                        $row['payroll_item_id'],

                    'employee_id' =>
                        $row['employee_id'],

                    'amount' =>
                        $row['amount'],

                    'payment_method' =>
                        $row['payment_method'],

                    'phone_number' =>
                        $row['phone_number'],

                    'bank_name' =>
                        $row['bank_name'],

                    'bank_account_number' =>
                        $row[
                            'bank_account_number'
                        ],

                    'transaction_reference' =>
                        $row[
                            'transaction_reference'
                        ],

                    'status' => 'draft',
                    'notes' => $row['notes'],
                ]);
            }

            $this->refreshDraftTotal($locked);

            return $locked->refresh()->load('items.employee');
        });
    }

    public function post(
        PayrollPayment $payment
    ): PayrollPayment {
        return DB::transaction(function () use ($payment): PayrollPayment {
            $locked = PayrollPayment::query()
                ->lockForUpdate()
                ->with([
                    'payroll',
                    'items.payrollItem',
                    'items.employee',
                ])
                ->findOrFail($payment->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft salary payments can be posted.',
                ]);
            }

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Add at least one employee salary payment.',
                ]);
            }

            $total = 0.0;

            foreach ($locked->items as $item) {
                $payrollItem = PayrollItem::query()
                    ->lockForUpdate()
                    ->findOrFail($item->payroll_item_id);

                $amount = round((float) $item->amount, 2);
                $outstanding = $this->itemOutstanding(
                    $payrollItem,
                    excludePaymentId: $locked->getKey()
                );

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$item->employee?->full_name}: "
                            . 'payment amount must be greater than zero.',
                    ]);
                }

                if ($amount > $outstanding + 0.01) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$item->employee?->full_name}: "
                            . 'payment exceeds the outstanding salary of '
                            . number_format($outstanding, 2)
                            . '.',
                    ]);
                }

                if (
                    $item->payment_method === 'bank'
                    && blank($item->bank_account_number)
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$item->employee?->full_name}: "
                            . 'bank account number is required.',
                    ]);
                }

                if (
                    in_array(
                        $item->payment_method,
                        ['mpesa', 'airtel_money'],
                        true
                    )
                    && blank($item->phone_number)
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$item->employee?->full_name}: "
                            . 'mobile money number is required.',
                    ]);
                }

                if (
                    $item->payment_method !== 'cash'
                    && blank($item->transaction_reference)
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$item->employee?->full_name}: "
                            . 'transaction reference or M-Pesa code is required.',
                    ]);
                }

                $item->forceFill([
                    'status' => 'posted',
                ])->saveQuietly();

                $total += $amount;
            }

            $locked->forceFill([
                'status' => 'posted',
                'total_amount' => round($total, 2),
                'posted_by' => auth()->id(),
                'posted_at' => now('Africa/Nairobi'),
            ])->saveQuietly();

            /*
             * Post after all child payment lines are complete. The same
             * posting key makes retries duplicate-safe.
             */
            app(AccountingIntegrationPostingService::class)
                ->postModel($locked, 'salary-payment-post');

            $this->synchronizePayroll($locked->payroll);

            return $locked->refresh()->load([
                'items.employee',
                'payroll',
            ]);
        });
    }

    public function reverse(
        PayrollPayment $payment,
        string $reason
    ): PayrollPayment {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reversal_reason' =>
                    'A salary payment reversal reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $payment,
            $reason
        ): PayrollPayment {
            $locked = PayrollPayment::query()
                ->lockForUpdate()
                ->with(['items', 'payroll'])
                ->findOrFail($payment->getKey());

            if (! $locked->isPosted()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only posted salary payments can be reversed.',
                ]);
            }

            app(AccountingIntegrationPostingService::class)
                ->reverseSource($locked, $reason);

            $locked->items()->update([
                'status' => 'reversed',
            ]);

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now('Africa/Nairobi'),
                'reversal_reason' => $reason,
            ])->saveQuietly();

            $this->synchronizePayroll($locked->payroll);

            return $locked->refresh();
        });
    }

    public function synchronizePayroll(
        Payroll $payroll
    ): void {
        $payroll->load('items');

        $totalPaid = 0.0;

        foreach ($payroll->items as $item) {
            $paid = (float) PayrollPaymentItem::query()
                ->where('payroll_item_id', $item->getKey())
                ->where('status', 'posted')
                ->sum('amount');

            $net = (float) $item->net_pay;

            $status = match (true) {
                $paid <= 0 => 'unpaid',
                $paid + 0.01 < $net => 'partial',
                default => 'paid',
            };

            $item->forceFill([
                'paid_amount' => round($paid, 2),
                'payment_status' => $status,
            ])->saveQuietly();

            $totalPaid += $paid;
        }

        $totalNet = (float) $payroll->items()->sum('net_pay');
        $balance = max(0, $totalNet - $totalPaid);

        $paymentStatus = match (true) {
            $totalPaid <= 0 => 'unpaid',
            $balance > 0.01 => 'partial',
            default => 'paid',
        };

        $payroll->forceFill([
            'total_net' => round($totalNet, 2),
            'total_paid' => round($totalPaid, 2),
            'balance_due' => round($balance, 2),
            'payment_status' => $paymentStatus,
        ])->saveQuietly();
    }

    public function itemOutstanding(
        PayrollItem $item,
        ?int $excludePaymentId = null
    ): float {
        $query = PayrollPaymentItem::query()
            ->where('payroll_item_id', $item->getKey())
            ->where('status', 'posted');

        if ($excludePaymentId) {
            $query->where(
                'payroll_payment_id',
                '!=',
                $excludePaymentId
            );
        }

        $paid = (float) $query->sum('amount');

        return round(
            max(0, (float) $item->net_pay - $paid),
            2
        );
    }

    public function refreshDraftTotal(
        PayrollPayment $payment
    ): void {
        $payment->forceFill([
            'total_amount' =>
                round((float) $payment->items()->sum('amount'), 2),
        ])->saveQuietly();
    }
}
