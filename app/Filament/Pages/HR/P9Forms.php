<?php

namespace App\Filament\Pages\HR;

use App\Models\HR\Employee;
use App\Models\HR\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use ZipArchive;

class P9Forms extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    //protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'P9 Form(s)';
    protected static ?string $title = 'P9 Form(s)';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.hr.p9-forms';

    public ?int $employee_id = null;
    public ?int $year = null;
    public ?int $bulkYear = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('print payslips') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('print payslips') ?? false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('print payslips'), 403);

        $currentYear = now()->year;

        $this->year = $currentYear;
        $this->bulkYear = $currentYear;

        $this->form->fill([
            'employee_id' => $this->employee_id,
            'year' => $this->year,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Generate KRA P9 Form')
                    ->description('Select an employee and a year to generate the KRA P9A tax deduction card.')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->options(
                                Employee::query()
                                    ->orderBy('full_name')
                                    ->pluck('full_name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(
                                collect(range(now()->year, now()->year - 5))
                                    ->mapWithKeys(fn (int $y) => [$y => (string) $y])
                                    ->toArray()
                            )
                            ->required()
                            ->default(now()->year)
                            ->native(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('');
    }

    public function generateP9()
    {
        abort_unless(auth()->user()?->can('print payslips'), 403);

        $data = $this->form->getState();

        $employee = Employee::query()->find($data['employee_id'] ?? null);

        if (! $employee) {
            Notification::make()
                ->danger()
                ->title('Employee not found')
                ->body('Please select a valid employee.')
                ->send();

            return null;
        }

        $year = (int) ($data['year'] ?? now()->year);

        $items = PayrollItem::query()
            ->with(['payroll'])
            ->where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) use ($year) {
                $query->where('year', $year);
            })
            ->get();

        $byMonth = $items->keyBy(fn (PayrollItem $item) => (int) ($item->payroll?->month ?? 0));

        $generatedBy = auth()->user()?->loadMissing('roles');

        $pdf = Pdf::loadView('pdf.hr.p9-form', [
            'employee' => $employee,
            'year' => $year,
            'byMonth' => $byMonth,
            'generatedBy' => $generatedBy,
        ])->setPaper('a4', 'landscape');

        $fileName = 'P9A_' .
            $this->safeFileName((string) ($employee->full_name ?: 'employee')) .
            '_' . $year . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $fileName
        );
    }

    public function generateBulkP9()
    {
        abort_unless(auth()->user()?->can('print payslips'), 403);

        $year = (int) ($this->bulkYear ?? now()->year);

        $employees = Employee::query()
            ->orderBy('full_name')
            ->get();

        if ($employees->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('No employees found')
                ->body('There are no employees available for bulk P9 generation.')
                ->send();

            return null;
        }

        $generatedBy = auth()->user()?->loadMissing('roles');

        $workingDir = storage_path('app/temp/p9-bulk-' . now()->format('Ymd_His') . '-' . uniqid());
        File::makeDirectory($workingDir, 0755, true, true);

        $zipFileName = 'P9A_Bulk_' . $year . '.zip';
        $zipPath = $workingDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Notification::make()
                ->danger()
                ->title('ZIP creation failed')
                ->body('Unable to create the ZIP archive.')
                ->send();

            return null;
        }

        foreach ($employees as $employee) {
            $items = PayrollItem::query()
                ->with(['payroll'])
                ->where('employee_id', $employee->id)
                ->whereHas('payroll', function ($query) use ($year) {
                    $query->where('year', $year);
                })
                ->get();

            $byMonth = $items->keyBy(fn (PayrollItem $item) => (int) ($item->payroll?->month ?? 0));

            $pdf = Pdf::loadView('pdf.hr.p9-form', [
                'employee' => $employee,
                'year' => $year,
                'byMonth' => $byMonth,
                'generatedBy' => $generatedBy,
            ])->setPaper('a4', 'landscape');

            $employeeFolder = $this->safeFileName((string) ($employee->full_name ?: 'employee'));
            $pdfFileName = 'P9A_' . $employeeFolder . '_' . $year . '.pdf';

            $zipFolderPath = $employeeFolder . '/';
            $zip->addEmptyDir($zipFolderPath);
            $zip->addFromString($zipFolderPath . $pdfFileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    protected function safeFileName(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_\- ]/', '', $value);
        $value = preg_replace('/\s+/', '_', trim($value));

        return $value !== '' ? $value : 'employee';
    }
}
