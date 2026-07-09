<?php

namespace App\Filament\Pages\HR;

use App\Filament\Clusters\HR\AttendanceRecords;
use App\Models\HR\AttendanceRecord;
use App\Models\HR\Employee;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\DB;

class BulkAttendanceCapture extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Bulk Attendance';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AttendanceRecords::class;

    protected static string $view = 'filament.pages.hr.bulk-attendance-capture';

    protected ?string $heading = '';

    public ?array $data = [];

    public int $summaryPresent = 0;

    public int $summaryLate = 0;

    public int $summaryAbsent = 0;

    public int $summaryLeave = 0;

    public int $summaryHalfDay = 0;

    public int $summaryHoliday = 0;

    public int $summaryOffDay = 0;

    public int $existingRecordsCount = 0;

    public function getTitle(): string
    {
        return '';
    }

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view attendance') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view attendance') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'attendance_date' => now()->toDateString(),
            'shift_name' => 'Day Shift',
            'expected_check_in' => '08:00',
            'expected_check_out' => '17:00',
            'department_filter' => null,
            'rows' => [],
        ]);

        $this->refreshSummary();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Defaults')
                    ->description('Set the standard times, filter by department if needed, then load employees.')
                    ->schema([
                        DatePicker::make('attendance_date')
                            ->label('Attendance Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->default(now())
                            ->live(),
                        TextInput::make('shift_name')
                            ->label('Shift Name')
                            ->default('Day Shift')
                            ->required(),
                        TimePicker::make('expected_check_in')
                            ->label('Expected Check In')
                            ->seconds(false)
                            ->default('08:00')
                            ->required()
                            ->live(),
                        TimePicker::make('expected_check_out')
                            ->label('Expected Check Out')
                            ->seconds(false)
                            ->default('17:00')
                            ->required()
                            ->live(),
                        Select::make('department_filter')
                            ->label('Department Filter')
                            ->options($this->getDepartmentOptions())
                            ->searchable()
                            ->preload()
                            ->placeholder('All Departments'),
                    ])
                    ->columns(5)
                    ->headerActions([
                        Action::make('loadEmployees')
                            ->label('Load Employees')
                            ->icon('heroicon-o-arrow-path')
                            ->color('primary')
                            ->visible(fn() => auth()->user()?->can('view attendance'))
                            ->action(fn() => $this->loadEmployees()),
                        Action::make('markAllPresent')
                            ->label('Mark All Present')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->visible(fn() => auth()->user()?->can('manage attendance'))
                            ->action(function () {
                                $this->markAllAs('present');
                                $this->persistRows();
                            }),
                        Action::make('markAllAbsent')
                            ->label('Mark All Absent')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->visible(fn() => auth()->user()?->can('manage attendance'))
                            ->action(function () {
                                $this->markAllAs('absent');
                                $this->persistRows();
                            }),
                        Action::make('biometricImport')
                            ->label('Import Biometric')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->color('gray')
                            ->visible(fn() => auth()->user()?->can('manage attendance'))
                            ->action(function () {
                                Notification::make()
                                    ->title('Biometric import placeholder')
                                    ->body('This slot is ready for device/file integration later.')
                                    ->info()
                                    ->send();
                            }),
                    ]),
                Forms\Components\Section::make('Daily Attendance Entries')
                    ->description('Edit exceptions only. Worked hours, overtime, and lateness are computed automatically.')
                    ->schema([
                        Repeater::make('rows')
                            ->label('')
                            ->schema([
                                TextInput::make('employee_id')
                                    ->label('Employee ID')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn($state) => $state ?: '')
                                    ->extraInputAttributes([
                                        'class' => 'font-medium bg-gray-50 dark:bg-white/5',
                                    ]),
                                TextInput::make('employee_name')
                                    ->label('Employee')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn($state) => $state ?: '')
                                    ->extraInputAttributes([
                                        'class' => 'font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-white/5',
                                    ]),
                                TextInput::make('shift_name')
                                    ->label('Shift')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn($state) => $state ?: '')
                                    ->extraInputAttributes([
                                        'class' => 'font-medium bg-gray-50 dark:bg-white/5',
                                    ]),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'present' => 'Present',
                                        'absent' => 'Absent',
                                        'late' => 'Late',
                                        'half_day' => 'Half Day',
                                        'on_leave' => 'On Leave',
                                        'holiday' => 'Holiday',
                                        'off_day' => 'Off Day',
                                    ])
                                    ->native(false)
                                    ->default('present')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        self::computeRow($get, $set);
                                        $this->syncRepeaterStateAndSummary();
                                    }),
                                TimePicker::make('check_in')
                                    ->label('Check In')
                                    ->seconds(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        self::computeRow($get, $set);
                                        $this->syncRepeaterStateAndSummary();
                                    }),
                                TimePicker::make('check_out')
                                    ->label('Check Out')
                                    ->seconds(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        self::computeRow($get, $set);
                                        $this->syncRepeaterStateAndSummary();
                                    }),
                                TextInput::make('hours_worked')
                                    ->label('Hours Worked')
                                    ->readOnly()
                                    ->numeric()
                                    ->dehydrated()
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => filled($state) ? number_format((float) $state, 2, '.', '') : '0.00')
                                    ->extraInputAttributes([
                                        'class' => 'font-medium bg-gray-50 dark:bg-white/5',
                                    ]),
                                TextInput::make('overtime_hours')
                                    ->label('Overtime')
                                    ->readOnly()
                                    ->numeric()
                                    ->dehydrated()
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => filled($state) ? number_format((float) $state, 2, '.', '') : '0.00')
                                    ->extraInputAttributes([
                                        'class' => 'font-medium bg-gray-50 dark:bg-white/5',
                                    ]),
                                TextInput::make('late_minutes')
                                    ->label('Late (Min)')
                                    ->readOnly()
                                    ->numeric()
                                    ->dehydrated()
                                    ->default(0)
                                    ->formatStateUsing(fn($state) => filled($state) ? (string) (int) $state : '0')
                                    ->extraInputAttributes([
                                        'class' => 'font-medium bg-gray-50 dark:bg-white/5',
                                    ]),
                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->default([])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['employee_name'] ?? 'Employee'),
                    ]),
            ])
            ->statePath('data');
    }

    public function loadEmployees(): void
    {
        $state = $this->form->getState();

        $attendanceDate = $state['attendance_date'] ?? now()->toDateString();
        $shiftName = $state['shift_name'] ?? 'Day Shift';
        $expectedCheckIn = $state['expected_check_in'] ?? '08:00';
        $expectedCheckOut = $state['expected_check_out'] ?? '17:00';
        $departmentId = $state['department_filter'] ?? null;

        $employees = Employee::query()
            ->when($departmentId, fn($query) => $query->where('department_id', $departmentId))
            ->orderBy('full_name')
            ->get();

        $existing = AttendanceRecord::query()
            ->whereDate('attendance_date', $attendanceDate)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $this->existingRecordsCount = $existing->count();

        $rows = $employees->map(function ($employee) use (
            $existing,
            $shiftName,
            $expectedCheckIn,
            $expectedCheckOut
        ) {
            $record = $existing->get($employee->id);

            $status = $record?->status?->value ?? $record?->status ?? 'present';
            $checkIn = $record?->check_in ? substr((string) $record->check_in, 0, 5) : $expectedCheckIn;
            $checkOut = $record?->check_out ? substr((string) $record->check_out, 0, 5) : $expectedCheckOut;
            $hoursWorked = (float) ($record?->hours_worked ?? 0);
            $overtimeHours = (float) ($record?->overtime_hours ?? 0);
            $lateMinutes = (int) ($record?->late_minutes ?? 0);

            if (!$record) {
                [$hoursWorked, $overtimeHours, $lateMinutes] = self::calculateMetrics(
                    $status,
                    $checkIn,
                    $checkOut,
                    $expectedCheckIn,
                    $expectedCheckOut
                );
            }

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'shift_name' => $record?->shift_name ?? $shiftName,
                'expected_check_in' => $expectedCheckIn,
                'expected_check_out' => $expectedCheckOut,
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'hours_worked' => number_format($hoursWorked, 2, '.', ''),
                'overtime_hours' => number_format($overtimeHours, 2, '.', ''),
                'late_minutes' => $lateMinutes,
                'remarks' => $record?->remarks,
            ];
        })->values()->all();

        $this->form->fill([
            ...$state,
            'rows' => $rows,
        ]);

        $this->syncRepeaterStateAndSummary();

        Notification::make()
            ->title('Employees loaded successfully')
            ->body(
                $this->existingRecordsCount > 0
                    ? "{$this->existingRecordsCount} existing attendance record(s) were found for this date and preloaded."
                    : 'Fresh attendance sheet loaded.'
            )
            ->success()
            ->send();
    }

    public function markAllAs(string $status): void
    {
        $state = $this->form->getState();
        $rows = $state['rows'] ?? [];
        $expectedCheckIn = $state['expected_check_in'] ?? '08:00';
        $expectedCheckOut = $state['expected_check_out'] ?? '17:00';

        if (empty($rows)) {
            Notification::make()
                ->title('No employees loaded')
                ->warning()
                ->send();

            return;
        }

        foreach ($rows as &$row) {
            $row['status'] = $status;

            if ($status === 'present') {
                $row['check_in'] = $expectedCheckIn;
                $row['check_out'] = $expectedCheckOut;
            }

            if (in_array($status, ['absent', 'on_leave', 'holiday', 'off_day'], true)) {
                $row['check_in'] = null;
                $row['check_out'] = null;
            }

            [$hoursWorked, $overtimeHours, $lateMinutes] = self::calculateMetrics(
                $row['status'] ?? 'present',
                $row['check_in'] ?? null,
                $row['check_out'] ?? null,
                $row['expected_check_in'] ?? $expectedCheckIn,
                $row['expected_check_out'] ?? $expectedCheckOut
            );

            $row['hours_worked'] = number_format($hoursWorked, 2, '.', '');
            $row['overtime_hours'] = number_format($overtimeHours, 2, '.', '');
            $row['late_minutes'] = $lateMinutes;
        }

        unset($row);

        $this->form->fill([
            ...$state,
            'rows' => $rows,
        ]);

        $this->syncRepeaterStateAndSummary();

        Notification::make()
            ->title('Rows updated')
            ->body('All employees have been set to ' . str_replace('_', ' ', $status) . '.')
            ->success()
            ->send();
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()?->can('manage attendance'),
            403
        );

        $saved = $this->persistRows();

        if ($saved) {
            Notification::make()
                ->title('Bulk attendance saved successfully')
                ->body(
                    $this->existingRecordsCount > 0
                        ? 'Existing records were updated and missing ones were created.'
                        : 'All records were created successfully.'
                )
                ->success()
                ->send();
        }
    }

    protected function persistRows(): bool
    {
        $state = $this->form->getState();

        $attendanceDate = $state['attendance_date'] ?? null;
        $rows = $state['rows'] ?? [];

        if (!$attendanceDate || empty($rows)) {
            Notification::make()
                ->title('Nothing to save')
                ->body('Please choose a date and load employees first.')
                ->warning()
                ->send();

            return false;
        }

        DB::transaction(function () use ($attendanceDate, $rows, $state) {
            foreach ($rows as $row) {
                [$hoursWorked, $overtimeHours, $lateMinutes] = self::calculateMetrics(
                    $row['status'] ?? 'present',
                    $row['check_in'] ?? null,
                    $row['check_out'] ?? null,
                    $row['expected_check_in'] ?? '08:00',
                    $row['expected_check_out'] ?? '17:00'
                );

                AttendanceRecord::updateOrCreate(
                    [
                        'employee_id' => $row['employee_id'],
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'check_in' => $row['check_in'] ?: null,
                        'check_out' => $row['check_out'] ?: null,
                        'shift_name' => $row['shift_name'] ?? ($state['shift_name'] ?? 'Day Shift'),
                        'hours_worked' => $hoursWorked,
                        'overtime_hours' => $overtimeHours,
                        'late_minutes' => $lateMinutes,
                        'status' => $row['status'] ?? 'present',
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );
            }
        });

        return true;
    }

    protected function getDepartmentOptions(): array
    {
        if (!class_exists(\App\Models\HR\Department::class)) {
            return [];
        }

        return \App\Models\HR\Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function refreshSummary(): void
    {
        $rows = data_get($this->data, 'rows', []);

        $this->summaryPresent = collect($rows)->where('status', 'present')->count();
        $this->summaryLate = collect($rows)->where('status', 'late')->count();
        $this->summaryAbsent = collect($rows)->where('status', 'absent')->count();
        $this->summaryLeave = collect($rows)->where('status', 'on_leave')->count();
        $this->summaryHalfDay = collect($rows)->where('status', 'half_day')->count();
        $this->summaryHoliday = collect($rows)->where('status', 'holiday')->count();
        $this->summaryOffDay = collect($rows)->where('status', 'off_day')->count();
    }

    protected function syncRepeaterStateAndSummary(): void
    {
        $this->data['rows'] = $this->form->getState()['rows'] ?? [];
        $this->refreshSummary();
    }

    protected static function computeRow(callable $get, callable $set): void
    {
        [$hoursWorked, $overtimeHours, $lateMinutes] = self::calculateMetrics(
            $get('status'),
            $get('check_in'),
            $get('check_out'),
            $get('expected_check_in'),
            $get('expected_check_out')
        );

        $set('hours_worked', number_format($hoursWorked, 2, '.', ''));
        $set('overtime_hours', number_format($overtimeHours, 2, '.', ''));
        $set('late_minutes', $lateMinutes);
    }

    protected static function calculateMetrics(
        ?string $status,
        ?string $checkIn,
        ?string $checkOut,
        ?string $expectedCheckIn,
        ?string $expectedCheckOut
    ): array {
        if (in_array($status, ['absent', 'on_leave', 'holiday', 'off_day'], true)) {
            return [0, 0, 0];
        }

        if (blank($checkIn) || blank($checkOut)) {
            return [0, 0, 0];
        }

        try {
            $actualIn = Carbon::createFromFormat('H:i', substr($checkIn, 0, 5));
            $actualOut = Carbon::createFromFormat('H:i', substr($checkOut, 0, 5));
            $expectedIn = Carbon::createFromFormat('H:i', substr($expectedCheckIn ?: '08:00', 0, 5));
            $expectedOut = Carbon::createFromFormat('H:i', substr($expectedCheckOut ?: '17:00', 0, 5));

            if ($actualOut->lessThanOrEqualTo($actualIn)) {
                return [0, 0, 0];
            }

            $workedMinutes = $actualIn->diffInMinutes($actualOut);
            $expectedShiftMinutes = $expectedIn->diffInMinutes($expectedOut);

            $hoursWorked = $workedMinutes / 60;
            $overtimeMinutes = max($workedMinutes - $expectedShiftMinutes, 0);
            $overtimeHours = $overtimeMinutes / 60;
            $lateMinutes = $actualIn->greaterThan($expectedIn)
                ? $expectedIn->diffInMinutes($actualIn)
                : 0;

            if ($status === 'late' && $lateMinutes === 0) {
                $lateMinutes = 1;
            }

            return [
                round($hoursWorked, 2),
                round($overtimeHours, 2),
                (int) $lateMinutes,
            ];
        } catch (\Throwable $e) {
            return [0, 0, 0];
        }
    }
}
