<?php

namespace App\Filament\Resources\HR;

use App\Enums\PayrollStatus;
use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\PayrollResource\Pages;
use App\Mail\HR\PayslipGeneratedMail;
use App\Models\HR\Payroll;
use App\Services\HR\Payroll\PayrollGenerationService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\Mail;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;
    // protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 10;

    use HasResourcePermissions;

    protected static string $permissionPrefix = 'payroll';

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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit payroll')),
                Action::make('printPayroll')
                    ->visible(fn() => auth()->user()?->can('export payroll'))
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
