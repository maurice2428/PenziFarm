<?php

namespace App\Filament\Resources\CasualPayrollResource\Pages;

use App\Filament\Resources\CasualPayrollResource;
use App\Models\HR\CasualPayroll;
use App\Models\HR\CasualPayrollItem;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ListCasualPayrolls extends ListRecords
{
    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected static string $resource = CasualPayrollResource::class;

    private const TEMPLATE_VERSION = 'CASUAL-PAYROLL-V1';

    private const HEADER_ROW = 8;

    protected function getHeaderWidgets(): array
    {
        return CasualPayrollResource::getWidgets();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn() => auth()->user()?->can('upload casual payroll') ?? false)
                ->form([
                    Forms\Components\DatePicker::make('week_start')
                        ->label('Week Start')
                        ->default(now()->startOfWeek(Carbon::SATURDAY))
                        ->required(),
                    Forms\Components\DatePicker::make('week_end')
                        ->label('Week End')
                        ->default(now()->startOfWeek(Carbon::SATURDAY)->addDays(6))
                        ->required(),
                    Forms\Components\TextInput::make('work_site')
                        ->label('Work Site')
                        ->placeholder('Example: Muserechi, Nakuru, Dhiwa')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2),
                ])
                ->modalHeading('Generate Casual Payroll Template')
                ->modalSubmitActionLabel('Download Excel')
                ->action(function (array $data) {
                    return $this->downloadTemplate($data);
                }),
            Actions\Action::make('uploadTemplate')
                ->label('Upload Filled Template')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('warning')
                ->visible(fn() => auth()->user()?->can('upload casual payroll') ?? false)
                ->form([
                    Forms\Components\Placeholder::make('guide')
                        ->label('Upload Guide')
                        ->content(new HtmlString('
                            <div style="line-height: 1.7">
                                <strong>Upload the ERP-generated casual payroll Excel template.</strong><br>
                                Fill worker names, ID numbers, phone numbers, designations, daily amounts, and signatures.<br>
                                Rows without a casual name will be skipped safely.
                            </div>
                        ')),
                    Forms\Components\FileUpload::make('excel_file')
                        ->label('Filled Excel File')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->modalHeading('Upload Casual Payroll')
                ->modalSubmitActionLabel('Import Payroll')
                ->action(function (array $data) {
                    $this->importTemplate($data);
                }),
            Actions\CreateAction::make()
                ->label('Create Manual Payroll')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->visible(fn() => auth()->user()?->can('create casual payroll') ?? false),
        ];
    }

    private function downloadTemplate(array $data)
    {
        $farmName = setting('farm.name', 'Lelekwe Farm Limited');

        $weekStart = Carbon::parse($data['week_start']);
        $weekEnd = Carbon::parse($data['week_end']);
        $dayHeaders = [];

        for ($i = 0; $i < 7; $i++) {
            $dayHeaders[] = $weekStart->copy()->addDays($i)->format('D d/m');
        }
        $workSite = trim((string) ($data['work_site'] ?? ''));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Casual Payroll');

        // $sheet->getDefaultStyle()->getFont()->setName('Courier New')->setSize(10);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Courier New')->setSize(10);

        $sheet->mergeCells('A1:N1');
        $sheet->setCellValue('A1', strtoupper($farmName));

        $sheet->mergeCells('A2:N2');
        $sheet->setCellValue('A2', 'CASUAL PAYROLL TEMPLATE');

        $sheet->setCellValue('A4', 'Template Version');
        $sheet->setCellValue('B4', self::TEMPLATE_VERSION);

        $sheet->setCellValue('D4', 'Week Start');
        $sheet->setCellValue('E4', $weekStart->format('Y-m-d'));

        $sheet->setCellValue('G4', 'Week End');
        $sheet->setCellValue('H4', $weekEnd->format('Y-m-d'));

        $sheet->setCellValue('J4', 'Work Site');
        $sheet->setCellValue('K4', $workSite);

        $sheet->setCellValue('A5', 'Generated At');
        $sheet->setCellValue('B5', now('Africa/Nairobi')->format('Y-m-d H:i:s'));

        $sheet->setCellValue('D5', 'Generated By');
        $sheet->setCellValue('E5', auth()->user()?->name ?? 'System');

        $sheet->mergeCells('G5:N6');
        $sheet->setCellValue('G5',
            "Instructions:\n"
                . "1. Fill casual worker details from row 9 downwards.\n"
                . "2. Put daily payment amount under each day worked.\n"
                . "3. Leave non-worked days blank or 0.\n"
                . '4. Do not edit Template Version, Week Start, or Week End.');

        /*$headers = [
            'No',
            'Casual Name',
            'ID Number',
            'Phone Number',
            'Designation',
            'Work Site',
            'Saturday',
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Signature',
        ];*/
        $headers = [
            'No',
            'Casual Name',
            'ID Number',
            'Phone Number',
            'Designation',
            'Work Site',
            'Daily Rate',
            $dayHeaders[0],
            $dayHeaders[1],
            $dayHeaders[2],
            $dayHeaders[3],
            $dayHeaders[4],
            $dayHeaders[5],
            $dayHeaders[6],
            'Signature',
            'Total',
        ];

        $sheet->fromArray($headers, null, 'A' . self::HEADER_ROW);

        /*for ($row = self::HEADER_ROW + 1; $row <= 60; $row++) {
            $sheet->setCellValue("A{$row}", $row - self::HEADER_ROW);
            $sheet->setCellValue("F{$row}", $workSite);
        }*/
        for ($row = self::HEADER_ROW + 1; $row <= 60; $row++) {
            $sheet->setCellValue("A{$row}", $row - self::HEADER_ROW);
            $sheet->setCellValue("F{$row}", $workSite);

            // Daily rate
            $sheet->setCellValue("G{$row}", 0);

            // Checkbox-style day cells
            foreach (range('H', 'N') as $column) {
                $sheet->setCellValue("{$column}{$row}", '');

                $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"P,"');
                $validation->setErrorTitle('Invalid Entry');
                $validation->setFormula1('"P,"');
                $validation->setError('Select P for present/worked day, or leave blank.');
                $validation->setPrompt('Select P if this casual worked on this day.');
                $validation->setPrompt('Select ☑ if this casual worked on this day.');
            }

            // Total = daily rate × checked days
            $sheet->setCellValue("P{$row}", "=G{$row}*COUNTIF(H{$row}:N{$row},\"P\")");
        }

        /* $totalRow = 62;

         $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
         $sheet->setCellValue("A{$totalRow}", 'TOTALS');

         foreach (range('G', 'M') as $column) {
             $sheet->setCellValue("{$column}{$totalRow}", "=SUM({$column}9:{$column}60)");
         }

         $sheet->setCellValue("N{$totalRow}", 'Authorized');

         $summaryRow = 64;
         $sheet->setCellValue("A{$summaryRow}", 'Grand Total');
         $sheet->setCellValue("B{$summaryRow}", "=SUM(G{$totalRow}:M{$totalRow})");

         $sheet->setCellValue("D{$summaryRow}", 'Total Casuals');
         $sheet->setCellValue("E{$summaryRow}", '=COUNTA(B9:B60)');

         $sheet->setCellValue("G{$summaryRow}", 'Total Days Worked');
         $sheet->setCellValue("H{$summaryRow}", '=COUNTIF(G9:M60,">0")');*/
        $totalRow = 62;

        $sheet->mergeCells("A{$totalRow}:O{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL PAYROLL AMOUNT');
        $sheet->setCellValue("P{$totalRow}", '=SUM(P9:P60)');

        $summaryRow = 64;

        $sheet->setCellValue("A{$summaryRow}", 'Grand Total');
        $sheet->setCellValue("B{$summaryRow}", '=SUM(P9:P60)');

        $sheet->setCellValue("D{$summaryRow}", 'Total Casuals');
        $sheet->setCellValue("E{$summaryRow}", '=COUNTIF(B9:B60,"<>")');

        $sheet->setCellValue("G{$summaryRow}", 'Total Days Worked');
        $sheet->setCellValue("H{$summaryRow}", '=COUNTIF(H9:N60,"P")');

        $sheet->getStyle('A1:N2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Courier New',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '008F00'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A4:N6')->applyFromArray([
            'font' => [
                'name' => 'Courier New',
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle('A' . self::HEADER_ROW . ':N' . self::HEADER_ROW)->applyFromArray([
            'font' => [
                'name' => 'Courier New',
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '111827'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A9:N60')->applyFromArray([
            'font' => [
                'name' => 'Courier New',
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // $sheet->getStyle('G9:M60')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        // $sheet->getStyle("G{$totalRow}:M{$totalRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        // $sheet->getStyle("B{$summaryRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet
            ->getStyle('G9:G60')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet
            ->getStyle('P9:P60')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet
            ->getStyle("P{$totalRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet
            ->getStyle("B{$summaryRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet->getStyle("A{$totalRow}:N{$totalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'name' => 'Courier New',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DCFCE7'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        $sheet->getStyle("A{$summaryRow}:H{$summaryRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'name' => 'Courier New',
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
        $sheet->getStyle('H9:N60')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'name' => 'Courier New',
                'bold' => true,
                'size' => 12,
            ],
        ]);

        $sheet->getStyle('P9:P60')->applyFromArray([
            'font' => [
                'name' => 'Courier New',
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECFDF5'],
            ],
        ]);

        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A9');
        $sheet->setAutoFilter('A' . self::HEADER_ROW . ':N60');

        $filename = 'casual-payroll-template-' . $weekStart->format('Ymd') . '-' . now()->format('His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function importTemplate(array $data): void
    {
        /** @var TemporaryUploadedFile $file */
        $file = $data['excel_file'];

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Casual Payroll') ?? $spreadsheet->getActiveSheet();

        $templateVersion = trim((string) ($sheet->getCell('B4')->getValue() ?? ''));

        if ($templateVersion !== self::TEMPLATE_VERSION) {
            Notification::make()
                ->danger()
                ->title('Invalid template')
                ->body('Please upload the official ERP-generated casual payroll template.')
                ->persistent()
                ->send();

            return;
        }

        $weekStart = $this->parseExcelDate($sheet->getCell('E4')->getValue());
        $weekEnd = $this->parseExcelDate($sheet->getCell('H4')->getValue());
        $workSite = trim((string) ($sheet->getCell('K4')->getValue() ?? ''));

        if (!$weekStart || !$weekEnd) {
            Notification::make()
                ->danger()
                ->title('Invalid payroll dates')
                ->body('Week Start or Week End is missing/invalid.')
                ->persistent()
                ->send();

            return;
        }

        DB::beginTransaction();

        try {
            $payroll = CasualPayroll::create([
                'farm_name' => setting('farm.name', 'Lelekwe Farm Limited'),
                'title' => 'Casual Payroll - ' . $weekStart->format('d M Y') . ' to ' . $weekEnd->format('d M Y'),
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'work_site' => $workSite ?: null,
                'uploaded_by' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $imported = 0;
            $skipped = 0;

            $totalAmount = 0;
            $totalDays = 0;

            $highestRow = $sheet->getHighestRow();

            for ($row = self::HEADER_ROW + 1; $row <= $highestRow; $row++) {
                $name = trim((string) ($sheet->getCell("B{$row}")->getValue() ?? ''));

                if ($name === '') {
                    $skipped++;
                    continue;
                }

                $idNumber = trim((string) ($sheet->getCell("C{$row}")->getValue() ?? ''));
                $phone = trim((string) ($sheet->getCell("D{$row}")->getValue() ?? ''));
                $designation = trim((string) ($sheet->getCell("E{$row}")->getValue() ?? ''));
                $itemWorkSite = trim((string) ($sheet->getCell("F{$row}")->getValue() ?? ''));

                /*
                 * $sat = $this->moneyValue($sheet->getCell("G{$row}")->getCalculatedValue());
                 * $sun = $this->moneyValue($sheet->getCell("H{$row}")->getCalculatedValue());
                 * $mon = $this->moneyValue($sheet->getCell("I{$row}")->getCalculatedValue());
                 * $tue = $this->moneyValue($sheet->getCell("J{$row}")->getCalculatedValue());
                 * $wed = $this->moneyValue($sheet->getCell("K{$row}")->getCalculatedValue());
                 * $thu = $this->moneyValue($sheet->getCell("L{$row}")->getCalculatedValue());
                 * $fri = $this->moneyValue($sheet->getCell("M{$row}")->getCalculatedValue());
                 */
                $dailyRate = $this->moneyValue($sheet->getCell("G{$row}")->getCalculatedValue());

                $satChecked = $this->isChecked($sheet->getCell("H{$row}")->getValue());
                $sunChecked = $this->isChecked($sheet->getCell("I{$row}")->getValue());
                $monChecked = $this->isChecked($sheet->getCell("J{$row}")->getValue());
                $tueChecked = $this->isChecked($sheet->getCell("K{$row}")->getValue());
                $wedChecked = $this->isChecked($sheet->getCell("L{$row}")->getValue());
                $thuChecked = $this->isChecked($sheet->getCell("M{$row}")->getValue());
                $friChecked = $this->isChecked($sheet->getCell("N{$row}")->getValue());

                $daysWorked = collect([
                    $satChecked,
                    $sunChecked,
                    $monChecked,
                    $tueChecked,
                    $wedChecked,
                    $thuChecked,
                    $friChecked,
                ])->filter()->count();

                $totalPay = $dailyRate * $daysWorked;

                $sat = $satChecked ? $dailyRate : 0;
                $sun = $sunChecked ? $dailyRate : 0;
                $mon = $monChecked ? $dailyRate : 0;
                $tue = $tueChecked ? $dailyRate : 0;
                $wed = $wedChecked ? $dailyRate : 0;
                $thu = $thuChecked ? $dailyRate : 0;
                $fri = $friChecked ? $dailyRate : 0;

                /*
                 * $daysWorked = collect([$sat, $sun, $mon, $tue, $wed, $thu, $fri])
                 *     ->filter(fn($amount) => (float) $amount > 0)
                 *     ->count();
                 *
                 * $totalPay = $sat + $sun + $mon + $tue + $wed + $thu + $fri;
                 */
                if ($totalPay <= 0) {
                    $skipped++;
                    continue;
                }

                CasualPayrollItem::create([
                    'casual_payroll_id' => $payroll->id,
                    'casual_name' => strtoupper($name),
                    'id_number' => $idNumber ?: null,
                    'phone_number' => $phone ?: null,
                    'designation' => $designation ?: null,
                    'work_site' => $itemWorkSite ?: $workSite ?: null,
                    'daily_rate' => $dailyRate,
                    'saturday_amount' => $sat,
                    'sunday_amount' => $sun,
                    'monday_amount' => $mon,
                    'tuesday_amount' => $tue,
                    'wednesday_amount' => $wed,
                    'thursday_amount' => $thu,
                    'friday_amount' => $fri,
                    'days_worked' => $daysWorked,
                    'total_pay' => $totalPay,
                    'signature' => $this->makeSignatureFromName($name),
                ]);

                $totalAmount += $totalPay;
                $totalDays += $daysWorked;
                $imported++;
            }

            $payroll->update([
                'total_casuals' => $imported,
                'total_days_worked' => $totalDays,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            Notification::make()
                ->success()
                ->title('Casual payroll imported')
                ->body("Imported: {$imported}. Skipped: {$skipped}. Total: KES " . number_format($totalAmount, 2))
                ->send();
        } catch (\Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Import failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
    private function makeSignatureFromName(?string $name): ?string
{
    $name = trim((string) $name);

    if ($name === '') {
        return null;
    }

    // Remove extra spaces
    $parts = preg_split('/\s+/', $name);

    if (! $parts || count($parts) === 0) {
        return null;
    }

    $firstName = $parts[0] ?? null;
    $lastName = count($parts) > 1 ? $parts[count($parts) - 1] : null;

    return trim(collect([$firstName, $lastName])
        ->filter()
        ->map(fn ($part) => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'))
        ->implode(' '));
}

    private function parseExcelDate(mixed $value): ?Carbon
    {
        try {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            }

            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function moneyValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $clean = preg_replace('/[^\d.\-]/', '', (string) $value);

        if ($clean === '' || !is_numeric($clean)) {
            return 0;
        }

        return round((float) $clean, 2);
    }

    private function isChecked(mixed $value): bool
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, [
            'P',
            'PRESENT',
            'YES',
            'Y',
            'TRUE',
            '1',
            'X',
            '☑',
            '✓',
            '✔',
        ], true);
    }
}
