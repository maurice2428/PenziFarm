<?php

namespace App\Filament\Resources\HR;

use App\Enums\PayrollStatus;
use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\PayrollResource\Pages;
use App\Filament\Resources\HR\PayrollPaymentResource;
use App\Filament\Resources\HR\StatutoryRemittanceResource;
use App\Mail\HR\PayslipGeneratedMail;
use App\Models\HR\Payroll;
use App\Services\HR\Payroll\PayrollGenerationService;
use App\Services\HR\Payroll\PayrollPaymentService;
use App\Services\HR\Payroll\PayrollLifecycleService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;
    // protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 10;

    use HasResourcePermissions;

    protected static string $permissionPrefix = 'payroll';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('period_start')
                ->label('Period Start')
                ->required()
                ->live(),
            Forms\Components\DatePicker::make('period_end')
                ->label('Period End')
                ->required()
                ->afterOrEqual('period_start'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'generated' => 'Generated',
                    'reviewed' => 'Reviewed',
                    'approved' => 'Approved',
                    'posted' => 'Posted',
                ])
                ->default('draft')
                ->required()
                ->native(false),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::create()->month((int) $state)->format('F'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('revision')
                    ->label('Run')
                    ->badge()
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            'R' . max(
                                1,
                                (int) $state
                            )
                    )
                    ->color(
                        fn (Payroll $record): string =>
                            $record->trashed()
                                ? 'gray'
                                : 'primary'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->date(),
                Tables\Columns\TextColumn::make('period_end')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof PayrollStatus ? ucfirst($state->value) : ucfirst((string) $state))
                    ->color(fn($state): string => match ($state instanceof PayrollStatus ? $state->value : (string) $state) {
                        'draft' => 'gray',
                        'generated' => 'success',
                        'reviewed' => 'warning',
                        'approved' => 'primary',
                        'posted' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Employees'),
                Tables\Columns\TextColumn::make('total_gross')
                    ->label('Gross Pay')->money('KES')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('total_net')
                    ->label('Net Pay')->money('KES')->sortable(),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Paid')->money('KES')->color('success')->toggleable(),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Salary Balance')->money('KES')->sortable()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(
                        fn ($state) => match ($state) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            default => 'danger',
                        }
                    ),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Archive')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            filled($state)
                                ? 'Archived'
                                : 'Active'
                    )
                    ->color(
                        fn ($state): string =>
                            filled($state)
                                ? 'gray'
                                : 'success'
                    )
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Archive Status'),
            ])
            ->actions([
                Action::make('payStaff')
                    ->label('Pay Staff')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(
                        fn (Payroll $record): bool =>
                            ! $record->trashed()
                            && $record->canReceivePayments()
                    )
                    ->action(function (Payroll $record) {
                        $payment = app(PayrollPaymentService::class)
                            ->createDraftForPayroll($record);

                        return redirect(PayrollPaymentResource::getUrl('edit', [
                            'record' => $payment,
                        ]));
                    }),
                Action::make('remitStatutory')
                    ->label('Statutory')
                    ->icon('heroicon-o-building-library')
                    ->color('warning')
                    ->visible(
                        fn (Payroll $record): bool =>
                            ! $record->trashed()
                            && in_array(
                                $record->statusValue(),
                                ['approved', 'posted'],
                                true
                            )
                    )
                    ->url(fn (Payroll $record): string =>
                        StatutoryRemittanceResource::getUrl('create', [
                            'payroll_id' => $record->getKey(),
                        ])),
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (Payroll $record): bool =>
                            ! $record->trashed()
                            && (
                                auth()->user()?->can(
                                    'edit payroll'
                                )
                                ?? false
                            )
                    ),
                Action::make('deletePayroll')
                    ->label('Delete / Reverse Payroll')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (Payroll $record): bool =>
                            ! $record->trashed()
                            && (
                                auth()->user()?->can(
                                    'delete payroll'
                                )
                                ?? false
                            )
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Reverse and archive this payroll?'
                    )
                    ->modalDescription(
                        'Posted salary payments, statutory remittances '
                        . 'and accounting journals will be reversed. '
                        . 'Generated payslips will be removed. The payroll '
                        . 'calculation remains archived for audit.'
                    )
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Deletion / Reversal Reason')
                            ->required()
                            ->minLength(5)
                            ->rows(3),
                    ])
                    ->action(
                        function (
                            Payroll $record,
                            array $data
                        ): void {
                            app(
                                PayrollLifecycleService::class
                            )->archiveAndReverse(
                                $record,
                                $data['reason']
                            );

                            Notification::make()
                                ->warning()
                                ->title(
                                    'Payroll reversed and archived'
                                )
                                ->body(
                                    'Payslips were removed and all posted '
                                    . 'salary/statutory/accounting effects '
                                    . 'were reversed.'
                                )
                                ->send();
                        }
                    ),

                Action::make('createNewRevision')
                    ->label('Create New Revision')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(
                        fn (Payroll $record): bool =>
                            $record->trashed()
                            && (
                                auth()->user()?->can(
                                    'create payroll'
                                )
                                ?? false
                            )
                    )
                    ->url(
                        fn (
                            Payroll $record
                        ): string =>
                            static::getUrl('create')
                            . '?'
                            . http_build_query([
                                'period_start' =>
                                    $record->period_start
                                        ?->format('Y-m-d'),
                                'period_end' =>
                                    $record->period_end
                                        ?->format('Y-m-d'),
                                'notes' =>
                                    'New revision created from archived '
                                    . $record->revision_label
                                    . '.',
                            ])
                    ),

                Action::make('restoreArchived')
                    ->label('Restore Archived')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(
                        fn (Payroll $record): bool =>
                            $record->trashed()
                            && (
                                auth()->user()?->can(
                                    'restore payroll'
                                )
                                ?? false
                            )
                    )
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Restore this exact historical payroll only when '
                        . 'there is no active payroll for the same month.'
                    )
                    ->action(
                        function (
                            Payroll $record
                        ): void {
                            app(
                                PayrollLifecycleService::class
                            )->restoreArchived($record);

                            Notification::make()
                                ->success()
                                ->title(
                                    'Archived payroll restored'
                                )
                                ->send();
                        }
                    ),

                Action::make('purgeArchived')
                    ->label('Permanently Delete Archive')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (Payroll $record): bool =>
                            $record->trashed()
                            && (
                                auth()->user()?->can(
                                    'force delete payroll'
                                )
                                ?? false
                            )
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Permanently delete archived payroll?'
                    )
                    ->modalDescription(
                        'This is allowed only when the archived payroll has '
                        . 'no accounting, salary-payment or statutory audit '
                        . 'history. Audit-protected records remain archived.'
                    )
                    ->action(
                        function (
                            Payroll $record
                        ): void {
                            app(
                                PayrollLifecycleService::class
                            )->purgeArchived($record);

                            Notification::make()
                                ->success()
                                ->title(
                                    'Archived payroll permanently deleted'
                                )
                                ->send();
                        }
                    ),

                Action::make('printPayroll')
                    ->visible(
                        fn (Payroll $record): bool =>
                            ! $record->trashed()
                            && (
                                auth()->user()?->can(
                                    'export payroll'
                                )
                                ?? false
                            )
                    )
                    ->label('Print Payroll')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function (Payroll $record) {
                        $record->load(['items.employee.department', 'items.employee.jobTitle']);

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hr.payroll-register', [
                            'payroll' => $record,
                            'items' => $record->items,
                            'generatedBy' => auth()->user(),
                        ])->setPaper('a4', 'landscape');

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            'payroll-register-' . $record->month . '-' . $record->year . '.pdf'
                        );
                    }),
                Action::make('generatePayroll')
                    ->visible(fn(Payroll $record) =>
                        auth()->user()?->can('generate payroll') &&
                        in_array(
                            $record->status instanceof PayrollStatus ? $record->status->value : $record->status,
                            ['draft', 'reviewed']
                        ))
                    ->label('Generate Payroll')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Payroll $record) => in_array(
                        $record->status instanceof PayrollStatus ? $record->status->value : $record->status,
                        ['draft', 'reviewed']
                    ))
                    ->action(function (Payroll $record, PayrollGenerationService $service) {
                        $service->generate($record);

                        Notification::make()
                            ->success()
                            ->title('Payroll and payslips generated successfully.')
                            ->body('You can now review the payroll and click "Email Payslips" when ready.')
                            ->send();
                    }),
                Action::make('emailPayslips')
                    ->visible(fn(Payroll $record) =>
                        auth()->user()?->can('email payslips') &&
                        in_array(
                            $record->status instanceof PayrollStatus ? $record->status->value : $record->status,
                            ['generated', 'approved', 'posted']
                        ) &&
                        $record->payslips()->count() > 0)
                    ->label('Email Payslips')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Toggle::make('force_resend')
                            ->label('Force resend already emailed payslips')
                            ->default(false),
                    ])
                    ->visible(fn(Payroll $record) => in_array(
                        $record->status instanceof PayrollStatus ? $record->status->value : $record->status,
                        ['generated', 'approved', 'posted']
                    ) && $record->payslips()->count() > 0)
                    ->action(function (Payroll $record, array $data) {
                        $forceResend = (bool) ($data['force_resend'] ?? false);

                        $record->load([
                            'payslips.employee.department',
                            'payslips.employee.jobTitle',
                            'payslips.payroll',
                        ]);

                        $total = $record->payslips->count();
                        $missingEmail = $record->payslips->filter(fn($p) => empty($p->employee?->email))->count();

                        if ($missingEmail > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Some employees are missing email addresses')
                                ->body("{$missingEmail} out of {$total} payslip(s) have no employee email and will be skipped.")
                                ->persistent()
                                ->send();
                        }

                        $sent = 0;
                        $failed = 0;
                        $skipped = 0;

                        foreach ($record->payslips as $payslip) {
                            $employee = $payslip->employee;
                            $email = $employee?->email;

                            if (!$employee || empty($email)) {
                                $skipped++;
                                continue;
                            }

                            if (!$forceResend && $payslip->email_sent) {
                                $skipped++;
                                continue;
                            }

                            try {
                                Mail::to($email)->send(new PayslipGeneratedMail($payslip));

                                $payslip->update([
                                    'email_sent' => true,
                                    'emailed_at' => now(),
                                ]);

                                $sent++;
                            } catch (\Throwable $e) {
                                report($e);
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Payslip emailing completed')
                            ->body("Total: {$total}. Sent: {$sent}. Failed: {$failed}. Skipped: {$skipped}.")
                            ->persistent()
                            ->send();
                    }),
                Action::make('approvePayroll')
                    ->visible(fn(Payroll $record) =>
                        auth()->user()?->can('approve payroll') &&
                        (
                            $record->status instanceof PayrollStatus ? $record->status->value : $record->status
                        ) === 'generated')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Payroll $record) => (
                        $record->status instanceof PayrollStatus ? $record->status->value : $record->status
                    ) === 'generated')
                    ->action(function (Payroll $record) {
                        if ($record->items()->count() === 0) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot approve payroll')
                                ->body('This payroll has no generated payroll items.')
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Payroll approved successfully.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('createSalaryPaymentDrafts')
                    ->label('Create Salary Payment Drafts')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $created = 0;
                        $skipped = 0;
                        foreach ($records as $record) {
                            if (! $record->canReceivePayments()) {
                                $skipped++;
                                continue;
                            }
                            app(PayrollPaymentService::class)
                                ->createDraftForPayroll($record);
                            $created++;
                        }
                        Notification::make()
                            ->title("{$created} salary payment draft(s) ready")
                            ->body("{$skipped} payroll(s) were skipped.")
                            ->color($skipped ? 'warning' : 'success')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make(
                    'deleteSelectedPayrolls'
                )
                    ->label('Delete / Reverse Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'delete payroll'
                            )
                            ?? false
                    )
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Common Reversal Reason')
                            ->required()
                            ->minLength(5)
                            ->rows(3),
                    ])
                    ->action(
                        function (
                            Collection $records,
                            array $data
                        ): void {
                            $archived = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->trashed()) {
                                    $skipped++;
                                    continue;
                                }

                                app(
                                    PayrollLifecycleService::class
                                )->archiveAndReverse(
                                    $record,
                                    $data['reason']
                                );

                                $archived++;
                            }

                            Notification::make()
                                ->title(
                                    "{$archived} payroll(s) reversed "
                                    . 'and archived'
                                )
                                ->body(
                                    "{$skipped} already archived "
                                    . 'record(s) were skipped.'
                                )
                                ->color(
                                    $skipped > 0
                                        ? 'warning'
                                        : 'success'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make(
                    'restoreArchivedSelected'
                )
                    ->label('Restore Selected Archived')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'restore payroll'
                            )
                            ?? false
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (
                            Collection $records
                        ): void {
                            $restored = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (! $record->trashed()) {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    app(
                                        PayrollLifecycleService::class
                                    )->restoreArchived(
                                        $record
                                    );

                                    $restored++;
                                } catch (
                                    \Illuminate\Validation\ValidationException
                                ) {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->title(
                                    "{$restored} archived payroll(s) restored"
                                )
                                ->body(
                                    "{$skipped} record(s) were skipped "
                                    . 'because an active payroll exists or '
                                    . 'the record was not archived.'
                                )
                                ->color(
                                    $skipped > 0
                                        ? 'warning'
                                        : 'success'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make(
                    'purgeArchivedSelected'
                )
                    ->label(
                        'Permanently Delete Eligible Archives'
                    )
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'force delete payroll'
                            )
                            ?? false
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (
                            Collection $records
                        ): void {
                            $deleted = 0;
                            $protected = 0;

                            foreach ($records as $record) {
                                if (
                                    ! $record->trashed()
                                    || ! app(
                                        PayrollLifecycleService::class
                                    )->canPurgeArchived(
                                        $record
                                    )
                                ) {
                                    $protected++;
                                    continue;
                                }

                                app(
                                    PayrollLifecycleService::class
                                )->purgeArchived($record);

                                $deleted++;
                            }

                            Notification::make()
                                ->title(
                                    "{$deleted} archived payroll(s) "
                                    . 'permanently deleted'
                                )
                                ->body(
                                    "{$protected} audit-protected or "
                                    . 'active record(s) were retained.'
                                )
                                ->color(
                                    $protected > 0
                                        ? 'warning'
                                        : 'success'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('exportSelected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Collection $records) {
                        return response()->streamDownload(function () use ($records): void {
                            $h = fopen('php://output', 'wb');
                            fputcsv($h, ['Month','Year','Status','Gross','Net','Paid','Balance']);
                            foreach ($records as $r) {
                                fputcsv($h, [$r->month,$r->year,$r->statusValue(),$r->total_gross,$r->total_net,$r->total_paid,$r->balance_due]);
                            }
                            fclose($h);
                        }, 'payroll-selected-' . now('Africa/Nairobi')->format('Ymd_His') . '.csv');
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
