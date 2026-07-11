<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\ChecksExplicitPermissions;
use App\Filament\Resources\HR\PayrollPaymentResource\Pages;
use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use App\Models\HR\PayrollPayment;
use App\Models\HR\PayrollPaymentItem;
use App\Services\HR\Payroll\PayrollPaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PayrollPaymentResource extends Resource
{
    use ChecksExplicitPermissions;

    protected static ?string $model = PayrollPayment::class;
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?string $navigationLabel = 'Salary Payments';
    protected static ?string $modelLabel = 'Salary Payment';
    protected static ?string $pluralModelLabel = 'Salary Payments';
    //protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return static::permits('view payroll payments');
    }

    public static function canCreate(): bool
    {
        return static::permits('create payroll payments');
    }

    public static function canEdit($record): bool
    {
        return static::permits('edit payroll payments')
            && $record->isDraft();
    }

    public static function canDelete($record): bool
    {
        return static::permits(
            'delete draft payroll payments'
        ) && $record->isDraft();
    }

    public static function canRestore($record): bool
    {
        return static::permits(
            'delete draft payroll payments'
        );
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    private static function clearSelectedPayroll(
        Forms\Set $set
    ): void {
        foreach ([
            'payroll_period_display',
            'payroll_employee_count',
            'payroll_gross_total',
            'payroll_employee_deductions',
            'payroll_net_total',
            'payroll_paid_total',
            'payroll_balance_total',
            'payroll_employer_statutories',
            'payroll_employer_cost',
        ] as $field) {
            $set($field, null);
        }

        $set('items', []);
        $set('total_amount', 0);
    }

    private static function loadSelectedPayroll(
        mixed $state,
        Forms\Set $set
    ): void {
        if (blank($state)) {
            static::clearSelectedPayroll($set);

            return;
        }

        $payroll = Payroll::query()
            ->with([
                'items.employee.department',
                'items.employee.jobTitle',
            ])
            ->find($state);

        if (! $payroll) {
            static::clearSelectedPayroll($set);

            return;
        }

        $service = app(
            PayrollPaymentService::class
        );

        foreach (
            $service->payrollSummary(
                $payroll
            ) as $field => $value
        ) {
            $set($field, $value);
        }

        $rows = $service->formRowsForPayroll(
            $payroll
        );

        $set('items', $rows);

        $set(
            'total_amount',
            round(
                (float) collect($rows)
                    ->sum('amount'),
                2
            )
        );
    }

    private static function refreshFormTotal(
        Forms\Get $get,
        Forms\Set $set
    ): void {
        $items = collect(
            $get('../../items') ?? []
        );

        $set(
            '../../total_amount',
            round(
                (float) $items->sum(
                    fn (array $row): float =>
                        max(
                            0,
                            (float) (
                                $row['amount'] ?? 0
                            )
                        )
                ),
                2
            )
        );
    }

    private static function applyPaymentDestination(
        mixed $method,
        Forms\Get $get,
        Forms\Set $set
    ): void {
        $employee = Employee::query()
            ->find($get('employee_id'));

        if (! $employee) {
            return;
        }

        match ($method) {
            'bank' => [
                $set(
                    'bank_name',
                    $employee->bank_name
                ),
                $set(
                    'bank_account_number',
                    $employee->account_number
                ),
                $set('phone_number', null),
            ],

            'mpesa' => [
                $set(
                    'phone_number',
                    $employee->mpesa_number
                ),
                $set('bank_name', null),
                $set(
                    'bank_account_number',
                    null
                ),
            ],

            'airtel_money' => [
                $set(
                    'phone_number',
                    $employee
                        ->airtel_money_number
                ),
                $set('bank_name', null),
                $set(
                    'bank_account_number',
                    null
                ),
            ],

            default => [
                $set('phone_number', null),
                $set('bank_name', null),
                $set(
                    'bank_account_number',
                    null
                ),
            ],
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make(
                    'Salary Payment Batch'
                )
                    ->description(
                        'Select an approved payroll. Employees, statutory '
                        . 'deductions, net salaries and saved payment '
                        . 'destinations will load immediately.'
                    )
                    ->icon('heroicon-o-banknotes')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make(
                            'payment_number'
                        )
                            ->label('Payment Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon(
                                'heroicon-o-hashtag'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                                'xl' => 3,
                            ]),

                        Forms\Components\Select::make(
                            'payroll_id'
                        )
                            ->label('Approved Payroll')
                            ->options(
                                fn (): array =>
                                    Payroll::query()
                                        ->whereIn(
                                            'status',
                                            [
                                                'approved',
                                                'posted',
                                            ]
                                        )
                                        ->where(
                                            'balance_due',
                                            '>',
                                            0
                                        )
                                        ->orderByDesc('year')
                                        ->orderByDesc('month')
                                        ->get()
                                        ->mapWithKeys(
                                            fn (
                                                Payroll $payroll
                                            ): array => [
                                                $payroll->id =>
                                                    sprintf(
                                                        '%s %s · %s staff · Balance KES %s',
                                                        \Carbon\Carbon::create()
                                                            ->month(
                                                                (int) $payroll
                                                                    ->month
                                                            )
                                                            ->format('F'),
                                                        $payroll->year,
                                                        $payroll
                                                            ->items()
                                                            ->count(),
                                                        number_format(
                                                            (float) $payroll
                                                                ->balance_due,
                                                            2
                                                        )
                                                    ),
                                            ]
                                        )
                                        ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                fn (
                                    mixed $state,
                                    Forms\Set $set
                                ): mixed =>
                                    static::loadSelectedPayroll(
                                        $state,
                                        $set
                                    )
                            )
                            ->disabled(
                                fn (
                                    ?PayrollPayment $record
                                ): bool =>
                                    filled($record)
                            )
                            ->dehydrated()
                            ->helperText(
                                'Only approved or posted payrolls with an '
                                . 'outstanding salary balance are shown.'
                            )
                            ->prefixIcon(
                                'heroicon-o-calendar-days'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'md' => 8,
                                'xl' => 5,
                            ]),

                        Forms\Components\DateTimePicker::make(
                            'payment_date'
                        )
                            ->label('Payment Date & Time')
                            ->default(
                                now('Africa/Nairobi')
                            )
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->prefixIcon(
                                'heroicon-o-clock'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                                'xl' => 4,
                            ]),

                        Forms\Components\TextInput::make(
                            'total_amount'
                        )
                            ->label(
                                'Selected Payment Total'
                            )
                            ->prefix('KES')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->readOnly()
                            ->dehydrated()
                            ->helperText(
                                'Updates automatically when an employee '
                                . 'amount is reduced for a partial payment.'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                                'xl' => 4,
                            ]),

                        Forms\Components\Select::make(
                            'status'
                        )
                            ->label('Batch Status')
                            ->options([
                                'draft' => 'Draft',
                                'posted' => 'Posted',
                                'reversed' => 'Reversed',
                            ])
                            ->default('draft')
                            ->disabled()
                            ->dehydrated()
                            ->prefixIcon(
                                'heroicon-o-check-badge'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                                'xl' => 3,
                            ]),

                        Forms\Components\Textarea::make(
                            'notes'
                        )
                            ->label('Batch Notes')
                            ->rows(2)
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                                'xl' => 5,
                            ]),
                    ]),

                Forms\Components\Section::make(
                    'Selected Payroll Snapshot'
                )
                    ->description(
                        'These figures come directly from the saved payroll '
                        . 'and cannot be changed from the payment screen.'
                    )
                    ->icon(
                        'heroicon-o-presentation-chart-line'
                    )
                    ->visible(
                        fn (
                            Forms\Get $get
                        ): bool =>
                            filled(
                                $get('payroll_id')
                            )
                    )
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make(
                            'payroll_period_display'
                        )
                            ->label('Payroll Period')
                            ->readOnly()
                            ->dehydrated(false)
                            ->prefixIcon(
                                'heroicon-o-calendar'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 3,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_employee_count'
                        )
                            ->label('Employees')
                            ->readOnly()
                            ->dehydrated(false)
                            ->prefixIcon(
                                'heroicon-o-user-group'
                            )
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 2,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_gross_total'
                        )
                            ->label('Gross Payroll')
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 2,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_employee_deductions'
                        )
                            ->label(
                                'Employee Deductions'
                            )
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 2,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_net_total'
                        )
                            ->label('Net Payroll')
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 3,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_paid_total'
                        )
                            ->label('Already Paid')
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 3,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_balance_total'
                        )
                            ->label('Outstanding Salary')
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 3,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_employer_statutories'
                        )
                            ->label(
                                'Employer Statutories'
                            )
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 3,
                            ]),

                        Forms\Components\TextInput::make(
                            'payroll_employer_cost'
                        )
                            ->label('Total Employer Cost')
                            ->prefix('KES')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 6,
                                'xl' => 3,
                            ]),
                    ]),

                Forms\Components\Section::make(
                    'Employees Being Paid'
                )
                    ->description(
                        'Loaded automatically from the saved payroll. '
                        . 'Salary and statutory figures are read-only. '
                        . 'Change only Amount to Pay, payment destination, '
                        . 'reference and notes.'
                    )
                    ->icon('heroicon-o-user-group')
                    ->visible(
                        fn (
                            Forms\Get $get,
                            ?PayrollPayment $record
                        ): bool =>
                            filled($record)
                            || filled(
                                $get('payroll_id')
                            )
                    )
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Repeater::make(
                            'items'
                        )
                            /*
                             * This is deliberately NOT a relationship
                             * repeater. The displayed payroll/statutory
                             * fields are virtual UI data and would be
                             * overwritten with nulls by Filament's
                             * relationship hydrator on Edit.
                             */
                            ->dehydrated(false)
                            ->label('Employee Payment Lines')
                            ->default([])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed(false)
                            ->itemLabel(
                                fn (
                                    array $state
                                ): ?string =>
                                    filled(
                                        $state[
                                            'employee_name'
                                        ] ?? null
                                    )
                                        ? (
                                            $state[
                                                'employee_name'
                                            ]
                                            . ' · Net KES '
                                            . number_format(
                                                (float) (
                                                    $state[
                                                        'net_pay'
                                                    ] ?? 0
                                                ),
                                                2
                                            )
                                            . ' · Paying KES '
                                            . number_format(
                                                (float) (
                                                    $state[
                                                        'amount'
                                                    ] ?? 0
                                                ),
                                                2
                                            )
                                        )
                                        : null
                            )
                            ->columns(12)
                            ->schema([
                                Forms\Components\Hidden::make(
                                    'payroll_item_id'
                                )
                                    ->required(),

                                Forms\Components\Hidden::make(
                                    'employee_id'
                                )
                                    ->required(),

                                Forms\Components\Hidden::make(
                                    'status'
                                )
                                    ->default('draft'),

                                Forms\Components\Fieldset::make(
                                    'Employee'
                                )
                                    ->columns(12)
                                    ->schema([
                                        Forms\Components\TextInput::make(
                                            'employee_name'
                                        )
                                            ->label('Employee Name')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->prefixIcon(
                                                'heroicon-o-user'
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 4,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'employee_number'
                                        )
                                            ->label('Employee No.')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'department_name'
                                        )
                                            ->label('Department')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'job_title_name'
                                        )
                                            ->label('Job Title')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),
                                    ]),

                                Forms\Components\Fieldset::make(
                                    'Saved Payroll Figures'
                                )
                                    ->columns(12)
                                    ->schema([
                                        Forms\Components\TextInput::make(
                                            'basic_salary'
                                        )
                                            ->label('Basic')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'allowances_total'
                                        )
                                            ->label('Allowances')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'gross_pay'
                                        )
                                            ->label('Gross')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'paye'
                                        )
                                            ->label('PAYE')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'nssf'
                                        )
                                            ->label('NSSF')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'sha'
                                        )
                                            ->label('SHIF')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'housing_levy'
                                        )
                                            ->label('Housing Levy')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'salary_advance_deduction'
                                        )
                                            ->label('Advance Recovery')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'other_deductions'
                                        )
                                            ->label('Other Deductions')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'deductions_total'
                                        )
                                            ->label('Total Deductions')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'net_pay'
                                        )
                                            ->label('Net Salary')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'already_paid'
                                        )
                                            ->label('Already Paid')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'outstanding_amount'
                                        )
                                            ->label('Outstanding')
                                            ->prefix('KES')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'default' => 12,
                                                'sm' => 6,
                                                'xl' => 2,
                                            ]),
                                    ]),

                                Forms\Components\Fieldset::make(
                                    'Payment Instruction'
                                )
                                    ->columns(12)
                                    ->schema([
                                        Forms\Components\TextInput::make(
                                            'amount'
                                        )
                                            ->label('Amount to Pay')
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->maxValue(
                                                fn (
                                                    Forms\Get $get
                                                ): float =>
                                                    max(
                                                        0,
                                                        (float) (
                                                            $get(
                                                                'outstanding_amount'
                                                            )
                                                            ?? 0
                                                        )
                                                    )
                                            )
                                            ->required()
                                            ->prefix('KES')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(
                                                fn (
                                                    Forms\Get $get,
                                                    Forms\Set $set
                                                ): mixed =>
                                                    static::refreshFormTotal(
                                                        $get,
                                                        $set
                                                    )
                                            )
                                            ->helperText(
                                                'Reduce this amount only '
                                                . 'when making a partial '
                                                . 'salary payment.'
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),

                                        Forms\Components\Select::make(
                                            'payment_method'
                                        )
                                            ->label('Payment Method')
                                            ->options([
                                                'bank' => 'Bank',
                                                'mpesa' => 'M-Pesa',
                                                'airtel_money' =>
                                                    'Airtel Money',
                                                'cash' => 'Cash',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(
                                                fn (
                                                    mixed $state,
                                                    Forms\Get $get,
                                                    Forms\Set $set
                                                ): mixed =>
                                                    static::applyPaymentDestination(
                                                        $state,
                                                        $get,
                                                        $set
                                                    )
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'phone_number'
                                        )
                                            ->label(
                                                'Mobile Money Number'
                                            )
                                            ->tel()
                                            ->visible(
                                                fn (
                                                    Forms\Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get(
                                                            'payment_method'
                                                        ),
                                                        [
                                                            'mpesa',
                                                            'airtel_money',
                                                        ],
                                                        true
                                                    )
                                            )
                                            ->required(
                                                fn (
                                                    Forms\Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get(
                                                            'payment_method'
                                                        ),
                                                        [
                                                            'mpesa',
                                                            'airtel_money',
                                                        ],
                                                        true
                                                    )
                                            )
                                            ->prefixIcon(
                                                'heroicon-o-device-phone-mobile'
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'bank_name'
                                        )
                                            ->label('Bank Name')
                                            ->visible(
                                                fn (
                                                    Forms\Get $get
                                                ): bool =>
                                                    $get(
                                                        'payment_method'
                                                    ) === 'bank'
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'bank_account_number'
                                        )
                                            ->label('Bank Account')
                                            ->visible(
                                                fn (
                                                    Forms\Get $get
                                                ): bool =>
                                                    $get(
                                                        'payment_method'
                                                    ) === 'bank'
                                            )
                                            ->required(
                                                fn (
                                                    Forms\Get $get
                                                ): bool =>
                                                    $get(
                                                        'payment_method'
                                                    ) === 'bank'
                                            )
                                            ->prefixIcon(
                                                'heroicon-o-building-library'
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 3,
                                            ]),

                                        Forms\Components\TextInput::make(
                                            'transaction_reference'
                                        )
                                            ->label(
                                                'Transaction Reference'
                                            )
                                            ->placeholder(
                                                'Enter when the payment '
                                                . 'has been executed'
                                            )
                                            ->helperText(
                                                'M-Pesa code, bank transfer '
                                                . 'reference or cash voucher.'
                                            )
                                            ->columnSpan([
                                                'default' => 12,
                                                'md' => 6,
                                                'xl' => 4,
                                            ]),

                                        Forms\Components\Textarea::make(
                                            'notes'
                                        )
                                            ->label('Employee Payment Notes')
                                            ->rows(2)
                                            ->columnSpan([
                                                'default' => 12,
                                                'xl' => 5,
                                            ]),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('payroll.month')
                    ->label('Payroll')
                    ->formatStateUsing(fn ($state, PayrollPayment $record): string =>
                        \Carbon\Carbon::create()->month((int) $state)->format('F')
                        . ' ' . $record->payroll?->year),
                Tables\Columns\TextColumn::make('payment_date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Employees'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PayrollPayment $record): string => match ($record->status) {
                        'posted' => 'success',
                        'reversed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('poster.name')
                    ->label('Posted By')
                    ->placeholder('Not posted')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed']),
                Tables\Filters\SelectFilter::make('payroll_id')
                    ->relationship('payroll', 'year')
                    ->label('Payroll'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (PayrollPayment $record): bool =>
                            static::permits(
                                'edit payroll payments'
                            )
                            && $record->isDraft()
                    ),
                Tables\Actions\Action::make('post')
                    ->label('Post Payment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (PayrollPayment $record): bool =>
                            static::permits(
                                'post payroll payments'
                            )
                            && $record->isDraft()
                    )
                    ->action(function (PayrollPayment $record): void {
                        app(PayrollPaymentService::class)->post($record);
                        Notification::make()->success()->title('Salary payment posted')->send();
                    }),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(
                        fn (PayrollPayment $record): bool =>
                            static::permits(
                                'reverse payroll payments'
                            )
                            && $record->isPosted()
                    )
                    ->form([
                        Forms\Components\Textarea::make('reason')->required()->minLength(5),
                    ])
                    ->action(function (PayrollPayment $record, array $data): void {
                        app(PayrollPaymentService::class)->reverse($record, $data['reason']);
                        Notification::make()->warning()->title('Salary payment reversed')->send();
                    }),
                Tables\Actions\Action::make('voucher')
                    ->label('Voucher')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (PayrollPayment $record): bool => ! $record->isDraft())
                    ->action(function (PayrollPayment $record) {
                        $record->load(['payroll', 'items.employee', 'poster']);
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hr.payroll-payment-voucher', [
                            'payment' => $record,
                            'generatedBy' => auth()->user(),
                        ])->setPaper('a4');

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $record->payment_number . '.pdf'
                        );
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (PayrollPayment $record): bool =>
                            static::permits(
                                'delete draft payroll payments'
                            )
                            && $record->isDraft()
                    ),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('postSelected')
                    ->label('Post Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'post payroll payments'
                        )
                    )
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $posted = 0;
                        foreach ($records as $record) {
                            if (! $record->isDraft()) {
                                continue;
                            }
                            app(PayrollPaymentService::class)->post($record);
                            $posted++;
                        }
                        Notification::make()->success()->title("{$posted} salary payment(s) posted")->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deleteDrafts')
                    ->label('Delete Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'delete draft payroll payments'
                        )
                    )
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->filter->isDraft()->each->delete();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollPayments::route('/'),
            'create' => Pages\CreatePayrollPayment::route('/create'),
            'edit' => Pages\EditPayrollPayment::route('/{record}/edit'),
        ];
    }
}
