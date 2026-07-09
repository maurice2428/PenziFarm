<?php

namespace App\Filament\Resources\HR;

use App\Filament\Clusters\HR\AttendanceRecords;
use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\AttendanceRecordResource\Pages;
use App\Models\HR\AttendanceRecord;
use App\Models\HR\Employee;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Mark Attendance';
    protected static ?int $navigationSort = 1;
    protected static ?string $cluster = AttendanceRecords::class;

    use HasResourcePermissions;

    protected static string $permissionPrefix = 'attendance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Attendance Details')
                ->description('Capture attendance and let the system compute worked hours, overtime, and lateness automatically.')
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->options(Employee::query()->orderBy('full_name')->pluck('full_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('attendance_date')
                        ->label('Attendance Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->default(now())
                        ->closeOnDateSelection(),
                    Forms\Components\TextInput::make('shift_name')
                        ->label('Shift Name')
                        ->placeholder('e.g. Day Shift')
                        ->default('Day Shift'),
                    Forms\Components\TimePicker::make('expected_check_in')
                        ->label('Expected Check In')
                        ->seconds(false)
                        ->default('08:00')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::computeAttendanceMetrics($get, $set))
                        ->helperText('Standard reporting time used to calculate late minutes.'),
                    Forms\Components\TimePicker::make('expected_check_out')
                        ->label('Expected Check Out')
                        ->seconds(false)
                        ->default('17:00')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::computeAttendanceMetrics($get, $set))
                        ->helperText('Standard leaving time used to calculate overtime.'),
                    Forms\Components\TimePicker::make('check_in')
                        ->label('Actual Check In')
                        ->seconds(false)
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::computeAttendanceMetrics($get, $set)),
                    Forms\Components\TimePicker::make('check_out')
                        ->label('Actual Check Out')
                        ->seconds(false)
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::computeAttendanceMetrics($get, $set)),
                    Forms\Components\Select::make('status')
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
                        ->required()
                        ->default('present')
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::computeAttendanceMetrics($get, $set)),
                    Forms\Components\TextInput::make('hours_worked')
                        ->label('Hours Worked')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated(true)
                        ->default(0)
                        ->prefixIcon('heroicon-o-clock')
                        ->helperText('Automatically computed from check-in and check-out.')
                        ->formatStateUsing(fn($state) => filled($state) ? number_format((float) $state, 2, '.', '') : '0.00')
                        ->extraInputAttributes([
                            'class' => 'font-semibold text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-white/5',
                        ]),
                    Forms\Components\TextInput::make('overtime_hours')
                        ->label('Overtime Hours')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated(true)
                        ->default(0)
                        ->prefixIcon('heroicon-o-bolt')
                        ->helperText('Computed from hours worked above the expected shift duration.')
                        ->formatStateUsing(fn($state) => filled($state) ? number_format((float) $state, 2, '.', '') : '0.00')
                        ->extraInputAttributes([
                            'class' => 'font-semibold text-amber-600 dark:text-amber-400 bg-gray-50 dark:bg-white/5',
                        ]),
                    Forms\Components\TextInput::make('late_minutes')
                        ->label('Late Minutes')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated(true)
                        ->default(0)
                        ->prefixIcon('heroicon-o-exclamation-circle')
                        ->helperText('Computed from expected reporting time versus actual check-in.')
                        ->formatStateUsing(fn($state) => filled($state) ? (string) (int) round((float) $state) : '0')
                        ->extraInputAttributes([
                            'class' => 'font-semibold text-rose-600 dark:text-rose-400 bg-gray-50 dark:bg-white/5',
                        ]),
                    Forms\Components\Textarea::make('remarks')
                        ->label('Remarks')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('adjustment_reason')
                        ->label('Adjustment Reason')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage attendance') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage attendance') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage attendance') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->label('Attendance Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check In')
                    ->time(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check Out')
                    ->time(),
                Tables\Columns\TextColumn::make('hours_worked')
                    ->label('Hours Worked')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('Overtime')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('late_minutes')
                    ->label('Late (Min)')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'half_day' => 'Half Day',
                        'on_leave' => 'On Leave',
                        'holiday' => 'Holiday',
                        'off_day' => 'Off Day',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('manage attendance')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceRecords::route('/'),
            'create' => Pages\CreateAttendanceRecord::route('/create'),
            'edit' => Pages\EditAttendanceRecord::route('/{record}/edit'),
        ];
    }

    protected static function computeAttendanceMetrics(Get $get, Set $set): void
    {
        $status = $get('status');
        $checkIn = $get('check_in');
        $checkOut = $get('check_out');
        $expectedCheckIn = $get('expected_check_in') ?: '08:00';
        $expectedCheckOut = $get('expected_check_out') ?: '17:00';

        if (in_array($status, ['absent', 'on_leave', 'holiday', 'off_day'], true)) {
            $set('hours_worked', number_format(0, 2, '.', ''));
            $set('overtime_hours', number_format(0, 2, '.', ''));
            $set('late_minutes', 0);
            return;
        }

        if (blank($checkIn) || blank($checkOut)) {
            $set('hours_worked', number_format(0, 2, '.', ''));
            $set('overtime_hours', number_format(0, 2, '.', ''));
            $set('late_minutes', 0);
            return;
        }

        try {
            $actualIn = Carbon::createFromFormat('H:i', substr($checkIn, 0, 5));
            $actualOut = Carbon::createFromFormat('H:i', substr($checkOut, 0, 5));
            $expectedIn = Carbon::createFromFormat('H:i', substr($expectedCheckIn, 0, 5));
            $expectedOut = Carbon::createFromFormat('H:i', substr($expectedCheckOut, 0, 5));

            if ($actualOut->lessThanOrEqualTo($actualIn)) {
                $set('hours_worked', number_format(0, 2, '.', ''));
                $set('overtime_hours', number_format(0, 2, '.', ''));
                $set('late_minutes', 0);
                return;
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

            $set('hours_worked', number_format($hoursWorked, 2, '.', ''));
            $set('overtime_hours', number_format($overtimeHours, 2, '.', ''));
            $set('late_minutes', (int) $lateMinutes);
        } catch (\Throwable $e) {
            $set('hours_worked', number_format(0, 2, '.', ''));
            $set('overtime_hours', number_format(0, 2, '.', ''));
            $set('late_minutes', 0);
        }
    }
}
