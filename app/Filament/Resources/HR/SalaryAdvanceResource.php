<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\SalaryAdvanceResource\Pages;
use App\Models\HR\Employee;
use App\Models\HR\SalaryAdvance;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class SalaryAdvanceResource extends Resource
{
    protected static ?string $model = SalaryAdvance::class;
    // protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'Salary Advances';
    protected static ?string $modelLabel = 'Salary Advance';
    protected static ?string $pluralModelLabel = 'Salary Advances';

    use HasResourcePermissions;

    protected static string $permissionPrefix = 'salary advances';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Employee & Request Details')
                ->description('Capture the employee, request date, and the reason for the advance.')
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->options(Employee::query()->orderBy('full_name')->pluck('full_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    Forms\Components\DatePicker::make('request_date')
                        ->label('Request Date')
                        ->required()
                        ->default(now())
                        ->native(false),
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason for Advance')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Approval & Repayment Terms')
                ->description('Set approved amount, repayment mode, schedule, and computed deductions.')
                ->schema([
                    Forms\Components\TextInput::make('amount_requested')
                        ->label('Amount Requested')
                        ->numeric()
                        ->prefix('KES')
                        ->required()
                        ->minValue(0)
                        ->live(),
                    Forms\Components\TextInput::make('amount_approved')
                        ->label('Amount Approved')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0)
                        ->minValue(0)
                        ->live(onBlur: true)
                        ->helperText('Leave at 0 while pending. On approval, it can default to requested amount.'),
                    Forms\Components\Select::make('repayment_mode')
                        ->label('Repayment Mode')
                        ->options([
                            'one_off' => 'One Off',
                            'installments' => 'Installments',
                        ])
                        ->default('installments')
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $approved = (float) ($get('amount_approved') ?: $get('amount_requested') ?: 0);

                            if ($state === 'one_off') {
                                $set('repayment_months', 1);
                                $set('monthly_deduction', round($approved, 2));
                            } else {
                                $months = max(1, (int) ($get('repayment_months') ?: 1));
                                $set('monthly_deduction', round($approved / $months, 2));
                            }
                        }),
                    Forms\Components\TextInput::make('repayment_months')
                        ->label('Repayment Months')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->visible(fn(Get $get) => $get('repayment_mode') === 'installments')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $approved = (float) ($get('amount_approved') ?: $get('amount_requested') ?: 0);
                            $months = max(1, (int) ($get('repayment_months') ?: 1));
                            $set('monthly_deduction', round($approved / $months, 2));
                        }),
                    Forms\Components\TextInput::make('monthly_deduction')
                        ->label('Monthly Deduction')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0)
                        ->required()
                        ->minValue(0)
                        ->helperText('Auto-calculated from approved amount and repayment term, but can be adjusted if needed.'),
                    Forms\Components\Select::make('approval_status')
                        ->label('Approval Status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false)
                        ->live(),
                    Forms\Components\TextInput::make('balance')
                        ->label('Outstanding Balance')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0)
                        ->minValue(0)
                        ->helperText('On approval, this should normally match the approved amount.')
                        ->readOnly(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('request_date')
                    ->label('Request Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_requested')
                    ->label('Requested')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_approved')
                    ->label('Approved')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('repayment_mode')
                    ->label('Repayment')
                    ->formatStateUsing(fn($state) => match ((string) $state) {
                        'one_off' => 'One Off',
                        'installments' => 'Installments',
                        default => ucfirst((string) $state),
                    })
                    ->badge()
                    ->color(fn($state) => (string) $state === 'one_off' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('repayment_months')
                    ->label('Months')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_deduction')
                    ->label('Monthly Deduction')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('KES')
                    ->sortable()
                    ->color(fn($state) => (float) $state > 0 ? 'warning' : 'success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => is_object($state) && isset($state->value) ? ucfirst($state->value) : ucfirst((string) $state))
                    ->color(fn($state) => match (is_object($state) && isset($state->value) ? $state->value : (string) $state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Approval Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('outstanding_only')
                    ->label('Outstanding Only')
                    ->query(fn($query) => $query->where('balance', '>', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit salary advances')),
                Action::make('print')
                    ->visible(fn() => auth()->user()?->can('export salary advances'))
                    ->label('Print Advance')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function (SalaryAdvance $record) {
                        $record->load(['employee.department', 'employee.jobTitle', 'approver']);

                        $pdf = Pdf::loadView('pdf.hr.salary-advance-single', [
                            'advance' => $record,
                            'employee' => $record->employee,
                            'generatedBy' => auth()->user(),
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            'salary-advance-' . $record->id . '.pdf'
                        );
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(SalaryAdvance $record) =>
                        auth()->user()?->can('approve salary advances') &&
                        (string) ($record->approval_status->value ?? $record->approval_status) === 'pending')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(SalaryAdvance $record) => (string) ($record->approval_status->value ?? $record->approval_status) === 'pending')
                    ->action(function (SalaryAdvance $record) {
                        $approved = (float) ($record->amount_approved > 0 ? $record->amount_approved : $record->amount_requested);
                        $months = max(1, (int) ($record->repayment_mode === 'one_off' ? 1 : $record->repayment_months));
                        $monthlyDeduction = $record->repayment_mode === 'one_off'
                            ? $approved
                            : round($approved / $months, 2);

                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'amount_approved' => $approved,
                            'balance' => $approved,
                            'monthly_deduction' => $monthlyDeduction,
                        ]);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(SalaryAdvance $record) => (string) ($record->approval_status->value ?? $record->approval_status) === 'pending')
                    ->action(function (SalaryAdvance $record) {
                        $record->update([
                            'approval_status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'amount_approved' => 0,
                            'monthly_deduction' => 0,
                            'balance' => 0,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label('Print Selected')
                        ->visible(fn() => auth()->user()?->can('export salary advances'))
                        ->icon('heroicon-o-printer')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->load([
                                'employee.department',
                                'employee.jobTitle',
                                'approver',
                            ]);

                            $pdf = Pdf::loadView('pdf.hr.salary-advances-bulk', [
                                'advances' => $records,
                                'generatedBy' => auth()->user(),
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn() => print ($pdf->output()),
                                'salary-advances-bulk-' . now()->format('Ymd_His') . '.pdf'
                            );
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete salary advances')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaryAdvances::route('/'),
            'create' => Pages\CreateSalaryAdvance::route('/create'),
            'edit' => Pages\EditSalaryAdvance::route('/{record}/edit'),
        ];
    }
}
