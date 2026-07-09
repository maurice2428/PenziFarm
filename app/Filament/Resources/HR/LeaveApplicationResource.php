<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\LeaveApplicationResource\Pages;
use App\Models\HR\Employee;
use App\Models\HR\LeaveApplication;
use App\Models\HR\LeaveType;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;
    // protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 8;

    use HasResourcePermissions;

    protected static string $permissionPrefix = 'leave applications';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Leave Application Details')
                ->description('Capture leave request details below. Days requested will be calculated automatically.')
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->options(Employee::query()->orderBy('full_name')->pluck('full_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('leave_type_id')
                        ->label('Leave Type')
                        ->options(LeaveType::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->live()
                        ->suffixIcon('heroicon-o-calendar-days')
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            self::computeDaysRequested($get, $set);
                        }),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->live()
                        ->minDate(fn(Get $get) => $get('start_date') ?: null)
                        ->suffixIcon('heroicon-o-calendar')
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            self::computeDaysRequested($get, $set);
                        }),
                    Forms\Components\TextInput::make('days_requested')
                        ->label('Days Requested')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated(true)
                        ->required()
                        ->prefixIcon('heroicon-o-calculator')
                        ->helperText('Automatically calculated from the selected start and end dates.')
                        ->formatStateUsing(fn($state) => filled($state) ? number_format((float) $state, 2, '.', '') : null)
                        ->extraInputAttributes([
                            'class' => 'font-semibold text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-white/5',
                        ]),
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->rows(4)
                        ->placeholder('Enter the reason for this leave request...')
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('attachment_path')
                        ->label('Attachment')
                        ->directory('leave-attachments')
                        ->downloadable()
                        ->openable()
                        ->previewable(),
                    Forms\Components\Select::make('approval_status')
                        ->label('Approval Status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('approval_notes')
                        ->label('Approval Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_requested')
                    ->label('Days Requested')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit leave applications')),
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(LeaveApplication $record) =>
                        auth()->user()?->can('approve leave applications') &&
                        optional($record->approval_status)->value === 'pending')
                    ->action(function (LeaveApplication $record): void {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(LeaveApplication $record) =>
                        auth()->user()?->can('approve leave applications') &&
                        optional($record->approval_status)->value === 'pending')
                    ->requiresConfirmation()
                    ->action(function (LeaveApplication $record): void {
                        $record->update([
                            'approval_status' => 'rejected',
                            'rejected_by' => auth()->id(),
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveApplications::route('/'),
            'create' => Pages\CreateLeaveApplication::route('/create'),
            'edit' => Pages\EditLeaveApplication::route('/{record}/edit'),
        ];
    }

    protected static function computeDaysRequested(Get $get, Set $set): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        if (blank($startDate) || blank($endDate)) {
            $set('days_requested', null);
            return;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();

            if ($end->lt($start)) {
                $set('days_requested', null);
                return;
            }

            // Inclusive count: same start/end date = 1 day
            $days = $start->diffInDays($end) + 1;

            $set('days_requested', number_format($days, 2, '.', ''));
        } catch (\Throwable $e) {
            $set('days_requested', null);
        }
    }
}
