<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\ChecksExplicitPermissions;
use App\Filament\Resources\HR\StatutoryRemittanceResource\Pages;
use App\Models\HR\Payroll;
use App\Models\HR\StatutoryRemittance;
use App\Services\HR\Payroll\StatutoryRemittanceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class StatutoryRemittanceResource extends Resource
{
    use ChecksExplicitPermissions;

    protected static ?string $model = StatutoryRemittance::class;
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?string $navigationLabel = 'Statutory Remittances';
    //protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?int $navigationSort = 12;

    public static function canViewAny(): bool
    {
        return static::permits(
            'view statutory remittances'
        );
    }

    public static function canCreate(): bool
    {
        return static::permits(
            'create statutory remittances'
        );
    }

    public static function canEdit($record): bool
    {
        return static::permits(
            'edit statutory remittances'
        ) && $record->isDraft();
    }

    public static function canDelete($record): bool
    {
        return static::permits(
            'delete draft statutory remittances'
        ) && $record->isDraft();
    }

    public static function canRestore($record): bool
    {
        return static::permits(
            'delete draft statutory remittances'
        );
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    protected static function fillFromPayroll(Forms\Set $set, Forms\Get $get): void
    {
        $payroll = Payroll::query()->find($get('payroll_id'));
        $type = $get('statutory_type');

        if (! $payroll || blank($type)) {
            return;
        }

        $prepared = app(StatutoryRemittanceService::class)
            ->prepare($payroll, $type);

        foreach ($prepared as $key => $value) {
            $set($key, $value);
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Statutory Obligation')
                    ->description('Record PAYE, NSSF, SHIF and Affordable Housing Levy remittances separately so each liability clears correctly.')
                    ->icon('heroicon-o-building-library')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('remittance_number')
                            ->label('Remittance Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('payroll_id')
                            ->label('Payroll')
                            ->options(fn (): array => Payroll::query()
                                ->whereIn('status', ['approved', 'posted'])
                                ->orderByDesc('year')
                                ->orderByDesc('month')
                                ->get()
                                ->mapWithKeys(fn (Payroll $payroll): array => [
                                    $payroll->id => \Carbon\Carbon::create()
                                        ->month((int) $payroll->month)->format('F')
                                        . ' ' . $payroll->year,
                                ])->all())
                            ->default(fn (): mixed => request()->integer('payroll_id') ?: null)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => static::fillFromPayroll($set, $get))
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('statutory_type')
                            ->label('Statutory Type')
                            ->options([
                                'paye' => 'PAYE',
                                'nssf' => 'NSSF (Employee + Employer)',
                                'shif' => 'SHIF',
                                'housing_levy' => 'Affordable Housing Levy (Employee + Employer)',
                            ])
                            ->native(false)
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => static::fillFromPayroll($set, $get))
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\DatePicker::make('period_start')
                            ->native(false)->required()->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\DatePicker::make('period_end')
                            ->native(false)->required()->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\DatePicker::make('due_date')
                            ->native(false)->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('amount_due')
                            ->label('Payroll Liability')
                            ->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                    ]),

                Forms\Components\Section::make('Payment Evidence')
                    ->description('Capture the PRN, bank or mobile reference and exact payment date and time.')
                    ->icon('heroicon-o-receipt-percent')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount Remitted')
                            ->numeric()->minValue(0.01)->prefix('KES')->required()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\DateTimePicker::make('payment_date')
                            ->label('Payment Date & Time')
                            ->seconds(false)->native(false)
                            ->default(now('Africa/Nairobi'))
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'bank' => 'Bank',
                                'mpesa' => 'M-Pesa / eCitizen',
                                'cash' => 'Cash',
                                'cheque' => 'Cheque',
                            ])
                            ->default('bank')->native(false)->required()->live()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('status')
                            ->options(['draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed'])
                            ->default('draft')->disabled()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('payment_registration_number')
                            ->label('PRN / Payment Registration Number')
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('bank_name')
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'bank')
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Payment Slip / Receipt')
                            ->directory('statutory-remittances')
                            ->downloadable()
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('period_end', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('remittance_number')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('type_label')->label('Type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('payroll.year')
                    ->label('Payroll')
                    ->formatStateUsing(fn ($state, StatutoryRemittance $record): string =>
                        ($record->payroll ? \Carbon\Carbon::create()->month((int) $record->payroll->month)->format('F') : 'N/A')
                        . ' ' . ($state ?? '')),
                Tables\Columns\TextColumn::make('due_date')
                    ->date('d M Y')
                    ->color(fn (StatutoryRemittance $record): string =>
                        $record->status === 'draft' && $record->due_date?->isPast() ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('amount_due')->money('KES')->toggleable(),
                Tables\Columns\TextColumn::make('amount')->money('KES')->sortable(),
                Tables\Columns\TextColumn::make('payment_registration_number')->label('PRN')->searchable()->placeholder('N/A'),
                Tables\Columns\TextColumn::make('transaction_reference')->label('Reference')->searchable()->placeholder('N/A'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'posted' => 'success', 'reversed' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statutory_type')->options([
                    'paye' => 'PAYE', 'nssf' => 'NSSF', 'shif' => 'SHIF', 'housing_levy' => 'Housing Levy',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (StatutoryRemittance $record): bool =>
                            static::permits(
                                'edit statutory remittances'
                            )
                            && $record->isDraft()
                    ),
                Tables\Actions\Action::make('post')
                    ->label('Post Remittance')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (StatutoryRemittance $record): bool =>
                            static::permits(
                                'post statutory remittances'
                            )
                            && $record->isDraft()
                    )
                    ->action(function (StatutoryRemittance $record): void {
                        app(StatutoryRemittanceService::class)->post($record);
                        Notification::make()->success()->title('Statutory remittance posted')->send();
                    }),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(
                        fn (StatutoryRemittance $record): bool =>
                            static::permits(
                                'reverse statutory remittances'
                            )
                            && $record->isPosted()
                    )
                    ->form([Forms\Components\Textarea::make('reason')->required()->minLength(5)])
                    ->action(function (StatutoryRemittance $record, array $data): void {
                        app(StatutoryRemittanceService::class)->reverse($record, $data['reason']);
                        Notification::make()->warning()->title('Remittance reversed')->send();
                    }),
                Tables\Actions\Action::make('voucher')
                    ->label('Voucher')->icon('heroicon-o-printer')->color('gray')
                    ->visible(fn (StatutoryRemittance $record): bool => ! $record->isDraft())
                    ->action(function (StatutoryRemittance $record) {
                        $record->load(['payroll', 'poster']);
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hr.statutory-remittance-voucher', [
                            'remittance' => $record,
                            'generatedBy' => auth()->user(),
                        ])->setPaper('a4');
                        return response()->streamDownload(fn () => print($pdf->output()), $record->remittance_number . '.pdf');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (StatutoryRemittance $record): bool =>
                            static::permits(
                                'delete draft statutory remittances'
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
                            'post statutory remittances'
                        )
                    )->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $posted = 0;
                        foreach ($records as $record) {
                            if (! $record->isDraft()) continue;
                            app(StatutoryRemittanceService::class)->post($record);
                            $posted++;
                        }
                        Notification::make()->success()->title("{$posted} remittance(s) posted")->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deleteDrafts')
                    ->label('Delete Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'delete draft statutory remittances'
                        )
                    )->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->filter->isDraft()->each->delete())
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStatutoryRemittances::route('/'),
            'create' => Pages\CreateStatutoryRemittance::route('/create'),
            'edit' => Pages\EditStatutoryRemittance::route('/{record}/edit'),
        ];
    }
}
