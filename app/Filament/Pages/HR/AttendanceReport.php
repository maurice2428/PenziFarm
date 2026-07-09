<?php

namespace App\Filament\Pages\HR;

use App\Filament\Clusters\HR\AttendanceRecords;
use App\Models\HR\AttendanceRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class AttendanceReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Attendance Report';
    protected static ?string $cluster = AttendanceRecords::class;
    protected static string $view = 'filament.pages.hr.attendance-report';

    protected ?string $heading = '';

    public ?array $filters = [];
    public array $records = [];

    public string $startDate = '';
    public string $endDate = '';

    public int $presentCount = 0;
    public int $lateCount = 0;
    public int $absentCount = 0;
    public int $leaveCount = 0;
    public float $totalHoursWorked = 0;
    public float $totalOvertimeHours = 0;

    public function getTitle(): string
    {
        return '';
    }

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public function mount(): void
    {
        $this->form->fill([
            'period_type' => 'daily',
            'report_date' => now()->toDateString(),
            'week_date' => now()->toDateString(),
            'month' => now()->format('Y-m'),
        ]);

        $this->loadReport();
    }
 public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view hr dashboard') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view hr dashboard') ?? false;
    }
    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('period_type')
                    ->label('Report Period')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                    ])
                    ->default('daily')
                    ->native(false)
                    ->live(),

                Forms\Components\DatePicker::make('report_date')
                    ->label('Daily Date')
                    ->native(false)
                    ->visible(fn ($get) => $get('period_type') === 'daily'),

                Forms\Components\DatePicker::make('week_date')
                    ->label('Week Reference Date')
                    ->helperText('Pick any date within the week.')
                    ->native(false)
                    ->visible(fn ($get) => $get('period_type') === 'weekly'),

                Forms\Components\TextInput::make('month')
                    ->label('Month')
                    ->type('month')
                    ->visible(fn ($get) => $get('period_type') === 'monthly'),
            ])
            ->columns(3)
            ->statePath('filters');
    }

    protected function resolveDates(): array
    {
        $filters = $this->form->getState();
        $periodType = $filters['period_type'] ?? 'daily';

        if ($periodType === 'weekly') {
            $base = Carbon::parse($filters['week_date'] ?? now()->toDateString());

            return [
                $base->copy()->startOfWeek(),
                $base->copy()->endOfWeek(),
            ];
        }

        if ($periodType === 'monthly') {
            $month = $filters['month'] ?? now()->format('Y-m');
            $base = Carbon::createFromFormat('Y-m', $month);

            return [
                $base->copy()->startOfMonth(),
                $base->copy()->endOfMonth(),
            ];
        }

        $date = Carbon::parse($filters['report_date'] ?? now()->toDateString());

        return [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
        ];
    }

    public function loadReport(): void
    {
        [$startDate, $endDate] = $this->resolveDates();

        $this->startDate = $startDate->toDateString();
        $this->endDate = $endDate->toDateString();

        $records = AttendanceRecord::query()
            ->with('employee')
            ->whereDate('attendance_date', '>=', $this->startDate)
            ->whereDate('attendance_date', '<=', $this->endDate)
            ->orderBy('attendance_date')
            ->orderBy('employee_id')
            ->get();

        $this->records = $records->map(function ($record) {
            $status = $record->status?->value ?? $record->status;

            return [
                'id' => $record->id,
                'attendance_date' => optional($record->attendance_date)->toDateString(),
                'employee_name' => $record->employee->full_name ?? '-',
                'status' => $status,
                'check_in' => $record->check_in ? substr((string) $record->check_in, 0, 5) : '-',
                'check_out' => $record->check_out ? substr((string) $record->check_out, 0, 5) : '-',
                'hours_worked' => (float) $record->hours_worked,
                'overtime_hours' => (float) $record->overtime_hours,
                'late_minutes' => (int) $record->late_minutes,
                'remarks' => $record->remarks ?: '-',
            ];
        })->all();

        $collection = collect($this->records);

        $this->presentCount = $collection->where('status', 'present')->count();
        $this->lateCount = $collection->where('status', 'late')->count();
        $this->absentCount = $collection->where('status', 'absent')->count();
        $this->leaveCount = $collection->where('status', 'on_leave')->count();
        $this->totalHoursWorked = (float) $collection->sum('hours_worked');
        $this->totalOvertimeHours = (float) $collection->sum('overtime_hours');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('pdf.attendance-report', [
            'records' => $this->records,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'generatedBy' => auth()->user(),
            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
            'presentCount' => $this->presentCount,
            'lateCount' => $this->lateCount,
            'absentCount' => $this->absentCount,
            'leaveCount' => $this->leaveCount,
            'totalHoursWorked' => $this->totalHoursWorked,
            'totalOvertimeHours' => $this->totalOvertimeHours,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'attendance-report.pdf'
        );
    }
}
