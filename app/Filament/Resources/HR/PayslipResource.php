<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\PayslipResource\Pages;
use App\Models\HR\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;
    // protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 11;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view payslips') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view payslips') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view payslips') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit payslips') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete payslips') ?? false;
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
                Tables\Columns\TextColumn::make('employee.employee_number')
                    ->label('Employee No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payroll.month')
                    ->label('Month')
                    ->formatStateUsing(fn($state) => Carbon::create()->month((int) $state)->format('F'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payroll.year')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_period_start')
                    ->label('Period Start')
                    ->date(),
                Tables\Columns\TextColumn::make('pay_period_end')
                    ->label('Period End')
                    ->date(),
                Tables\Columns\TextColumn::make('gross_pay')
                    ->label('Gross Pay')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_pay')
                    ->label('Net Pay')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('email_sent')
                    ->label('Emailed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('emailed_at')
                    ->label('Emailed At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payroll.month')
                    ->label('Month')
                    ->options([
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December',
                    ]),
                Tables\Filters\SelectFilter::make('payroll.year')
                    ->label('Year')
                    ->relationship('payroll', 'year'),
                Tables\Filters\TernaryFilter::make('email_sent')
                    ->label('Email Sent'),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->visible(fn() => auth()->user()?->can('print payslips'))
                    ->label('Print Payslip')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (Payslip $record) {
                        $record->load(['employee.department', 'employee.jobTitle', 'payroll']);

                        $pdf = Pdf::loadView('pdf.hr.payslip', [
                            'payslip' => $record,
                            'employee' => $record->employee,
                            'payroll' => $record->payroll,
                            'generatedBy' => auth()->user(),
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            'payslip-'
                                . ($record->employee->employee_number ?? $record->employee_id)
                                . '-'
                                . ($record->payroll->month ?? 'm')
                                . '-'
                                . ($record->payroll->year ?? 'y')
                                . '.pdf'
                        );
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit payslips'))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->visible(fn() => auth()->user()?->can('print payslips'))
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->load(['employee.department', 'employee.jobTitle', 'payroll']);

                            $pdf = Pdf::loadView('pdf.hr.payslips-bulk', [
                                'payslips' => $records,
                                'generatedBy' => auth()->user(),
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn() => print ($pdf->output()),
                                'selected-payslips-' . now()->format('Ymd_His') . '.pdf'
                            );
                        }),
                    Tables\Actions\BulkAction::make('downloadZip')
                        ->label('Download ZIP')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->load(['employee.department', 'employee.jobTitle', 'payroll']);

                            $timestamp = now()->format('Ymd_His');
                            $baseTempDir = storage_path('app/temp/payslip_zip_' . $timestamp);
                            $zipFilePath = storage_path('app/temp/selected-payslips-' . $timestamp . '.zip');

                            if (!File::exists(dirname($zipFilePath))) {
                                File::makeDirectory(dirname($zipFilePath), 0755, true);
                            }

                            if (File::exists($baseTempDir)) {
                                File::deleteDirectory($baseTempDir);
                            }

                            File::makeDirectory($baseTempDir, 0755, true);

                            $zip = new ZipArchive();

                            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                                throw new \RuntimeException('Unable to create ZIP file.');
                            }

                            foreach ($records as $record) {
                                $employeeName = $record->employee->full_name ?? 'Unknown Employee';
                                $employeeNumber = $record->employee->employee_number ?? (string) $record->employee_id;

                                $monthNumber = (int) ($record->payroll->month ?? 1);
                                $yearNumber = (int) ($record->payroll->year ?? now()->year);

                                $monthName = Carbon::create()->month($monthNumber)->format('F');
                                $monthFolder = self::safeFileName($monthName . '-' . $yearNumber);
                                $employeeFolder = self::safeFileName($employeeName);

                                $pdfFileName = self::safeFileName(
                                    'payslip-'
                                    . $employeeName . '-'
                                    . $employeeNumber . '-'
                                    . $monthName . '-'
                                    . $yearNumber
                                ) . '.pdf';

                                $relativePathInZip = $monthFolder . '/' . $employeeFolder . '/' . $pdfFileName;
                                $fullTempPdfPath = $baseTempDir . DIRECTORY_SEPARATOR . $relativePathInZip;

                                if (!File::exists(dirname($fullTempPdfPath))) {
                                    File::makeDirectory(dirname($fullTempPdfPath), 0755, true);
                                }

                                $pdf = Pdf::loadView('pdf.hr.payslip', [
                                    'payslip' => $record,
                                    'employee' => $record->employee,
                                    'payroll' => $record->payroll,
                                    'generatedBy' => auth()->user(),
                                ])->setPaper('a4', 'portrait');

                                File::put($fullTempPdfPath, $pdf->output());

                                $zip->addFile($fullTempPdfPath, $relativePathInZip);
                            }

                            $zip->close();

                            File::deleteDirectory($baseTempDir);

                            return response()->download($zipFilePath)->deleteFileAfterSend(true);
                        }),
                ]),
                Tables\Actions\BulkAction::make('deleteSelected')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->visible(fn() => auth()->user()?->can('delete payslips'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Payslips')
                    ->modalDescription('Are you sure you want to delete the selected payslips? This action cannot be undone.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        $count = $records->count();

                        foreach ($records as $record) {
                            if (!empty($record->pdf_path)) {
                                $fullPath = storage_path('app/public/' . ltrim($record->pdf_path, '/'));

                                if (\Illuminate\Support\Facades\File::exists($fullPath)) {
                                    \Illuminate\Support\Facades\File::delete($fullPath);
                                }
                            }

                            $record->delete();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Payslips deleted')
                            ->body("Deleted {$count} payslip(s) successfully.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayslips::route('/'),
            'edit' => Pages\EditPayslip::route('/{record}/edit'),
        ];
    }

    protected static function safeFileName(string $value): string
    {
        $value = Str::of($value)
            ->replaceMatches('/[\/\\\\\\?\%\*\:\|"<>\.]+/', ' ')
            ->squish()
            ->replace(' ', '-')
            ->toString();

        return trim($value, '-');
    }
}
