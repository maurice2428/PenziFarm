<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\EmployeeResource\Pages;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class EmployeeResource extends Resource
{
    use HasResourcePermissions;

    protected static string $permissionPrefix = 'employees';
    protected static ?string $model = Employee::class;
    // protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Personal Information')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Section::make('Employee Identity')
                            ->description('Basic employee details and profile photo.')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])->schema([
                                    /*
                                     * TextInput::make('employee_number')
                                     *     ->label('Employee No.')
                                     *     ->required()
                                     *     ->unique(ignoreRecord: true)
                                     *     ->maxLength(50)
                                     *     ->placeholder('EMP-001'),
                                     *
                                     * TextInput::make('employee_number')
                                     *     ->label('Employee No.')
                                     *     ->disabled() // readonly UI
                                     *     ->dehydrated(true) // still saved
                                     *     ->unique(ignoreRecord: true)
                                     *     ->placeholder('Auto-generated (LLKSTF001)')
                                     *
                                     * TextInput::make('employee_number')
                                     *     ->label('Employee No.')
                                     *     ->disabled()
                                     *     ->dehydrated(false)
                                     *     ->placeholder('Auto-generated'),
                                     */
                                    TextInput::make('employee_number')
                                        ->hidden()
                                        ->dehydrated(false),
                                    TextInput::make('first_name')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('middle_name')
                                        ->maxLength(100),
                                    TextInput::make('last_name')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('id_passport_number')
                                        ->label('ID / Passport No.')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(50),
                                    TextInput::make('kra_pin')
                                        ->label('KRA PIN')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(30),
                                    TextInput::make('nssf_number')
                                        ->label('NSSF No.')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(30),
                                    TextInput::make('nhif_sha_number')
                                        ->label('NHIF / SHA No.')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(30),
                                    TextInput::make('phone')
                                        ->tel()
                                        ->required()
                                        ->maxLength(20)
                                        ->placeholder('07XXXXXXXX'),
                                    TextInput::make('alternate_phone')
                                        ->tel()
                                        ->maxLength(20)
                                        ->placeholder('07XXXXXXXX'),
                                    TextInput::make('email')
                                        ->email()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    Select::make('gender')
                                        ->options([
                                            'male' => 'Male',
                                            'female' => 'Female',
                                            'other' => 'Other',
                                        ])
                                        ->native(false),

                                    /*
                                     * DatePicker::make('date_of_birth'),
                                     */
                                    DatePicker::make('date_of_birth')
                                        ->label('Date of Birth')
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->closeOnDateSelection(),
                                    Select::make('marital_status')
                                        ->options([
                                            'single' => 'Single',
                                            'married' => 'Married',
                                            'divorced' => 'Divorced',
                                            'widowed' => 'Widowed',
                                        ])
                                        ->native(false),
                                    TextInput::make('county')
                                        ->maxLength(100),
                                    TextInput::make('address')
                                        ->maxLength(255),
                                    TextInput::make('postal_address')
                                        ->maxLength(255),
                                ]),
                                FileUpload::make('avatar_path')
                                    ->label('Employee Avatar')
                                    ->disk('public')
                                    ->directory('employees/avatars')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                        'image/gif',
                                    ])
                                    ->maxSize(2048)
                                    ->imageEditor()
                                    ->avatar()
                                    ->circleCropper()
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Upload JPG, PNG, WEBP, or GIF only. Max size: 2MB.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Wizard\Step::make('Employment')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Section::make('Employment Details')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])->schema([
                                    DatePicker::make('hire_date')
                                        ->required(),
                                    Select::make('department_id')
                                        ->label('Department')
                                        ->options(Department::query()->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    Select::make('job_title_id')
                                        ->label('Job Title')
                                        ->options(JobTitle::query()->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    Select::make('reporting_manager_id')
                                        ->label('Reporting Manager')
                                        ->options(Employee::query()->pluck('full_name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    Select::make('employment_type')
                                        ->options([
                                            'permanent' => 'Permanent',
                                            'contract' => 'Contract',
                                            'casual' => 'Casual',
                                            'internship' => 'Internship',
                                            'probation' => 'Probation',
                                        ])
                                        ->native(false),
                                    TextInput::make('work_station')
                                        ->label('Work Station')
                                        ->maxLength(150),
                                    Select::make('status')
                                        ->required()
                                        ->live()
                                        ->options([
                                            'active' => 'Active',
                                            'inactive' => 'Inactive',
                                            'exited' => 'Exited',
                                            'suspended' => 'Suspended',
                                        ])
                                        ->default('active')
                                        ->native(false)
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $set('is_active', $state === 'active');
                                        }),
                                    Toggle::make('is_active')
                                        ->label('Currently Active')
                                        ->default(true)
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->helperText('Automatically controlled by employment status.'),
                                    DatePicker::make('contract_start_date')
                                        ->visible(fn(Get $get) => in_array($get('employment_type'), ['contract', 'probation'])),
                                    DatePicker::make('contract_end_date')
                                        ->visible(fn(Get $get) => in_array($get('employment_type'), ['contract', 'probation'])),
                                ]),
                            ]),
                    ]),
                Wizard\Step::make('Payroll & Benefits')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Section::make('Salary Details')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])->schema([
                                    TextInput::make('basic_salary')
                                        ->numeric()
                                        ->default(0)
                                        ->required()
                                        ->prefix('KES'),
                                    TextInput::make('house_allowance')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('KES'),
                                    TextInput::make('transport_allowance')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('KES'),
                                    TextInput::make('other_allowance')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('KES'),
                                ]),
                            ]),
                        Section::make('Payment Method')
                            ->description('Choose how the employee should receive salary payments.')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])->schema([
                                    Select::make('payment_method')
                                        ->label('Salary Payment Method')
                                        ->options([
                                            'bank' => 'Bank',
                                            'mpesa' => 'M-Pesa',
                                            'airtel_money' => 'Airtel Money',
                                        ])
                                        ->native(false)
                                        ->live()
                                        ->required()
                                        ->default('bank'),
                                    Select::make('bank_name')
                                        ->options([
                                            'KCB Bank' => 'KCB Bank',
                                            'Equity Bank' => 'Equity Bank',
                                            'Co-operative Bank' => 'Co-operative Bank',
                                            'Absa Bank' => 'Absa Bank',
                                            'NCBA Bank' => 'NCBA Bank',
                                            'Family Bank' => 'Family Bank',
                                            'Stanbic Bank' => 'Stanbic Bank',
                                            'I&M Bank' => 'I&M Bank',
                                            'DTB Bank' => 'DTB Bank',
                                            'Standard Chartered' => 'Standard Chartered',
                                            'Prime Bank' => 'Prime Bank',
                                            'Kingdom Bank' => 'Kingdom Bank',
                                            'Postbank' => 'Postbank',
                                            'Other' => 'Other',
                                        ])
                                        ->searchable()
                                        ->native(false)
                                        ->visible(fn(Get $get) => $get('payment_method') === 'bank')
                                        ->required(fn(Get $get) => $get('payment_method') === 'bank'),
                                    TextInput::make('bank_branch')
                                        ->visible(fn(Get $get) => $get('payment_method') === 'bank')
                                        ->required(fn(Get $get) => $get('payment_method') === 'bank'),
                                    TextInput::make('account_number')
                                        ->label('Bank Account Number')
                                        ->visible(fn(Get $get) => $get('payment_method') === 'bank')
                                        ->required(fn(Get $get) => $get('payment_method') === 'bank'),
                                    TextInput::make('mpesa_number')
                                        ->label('Safaricom / M-Pesa Number')
                                        ->tel()
                                        ->placeholder('07XXXXXXXX')
                                        ->visible(fn(Get $get) => $get('payment_method') === 'mpesa')
                                        ->required(fn(Get $get) => $get('payment_method') === 'mpesa'),
                                    TextInput::make('airtel_money_number')
                                        ->label('Airtel Money Number')
                                        ->tel()
                                        ->placeholder('07XXXXXXXX')
                                        ->visible(fn(Get $get) => $get('payment_method') === 'airtel_money')
                                        ->required(fn(Get $get) => $get('payment_method') === 'airtel_money'),
                                ]),
                            ]),
                        Section::make('Statutory Deductions')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])->schema([
                                    Toggle::make('tax_enabled')
                                        ->label('Apply PAYE')
                                        ->default(true),
                                    Toggle::make('nssf_enabled')
                                        ->label('Apply NSSF')
                                        ->default(true),
                                    Toggle::make('sha_enabled')
                                        ->label('Apply SHA')
                                        ->default(true),
                                    Toggle::make('housing_levy_enabled')
                                        ->label('Apply Housing Levy')
                                        ->default(true),
                                ]),
                            ]),
                    ]),
                Wizard\Step::make('Documents & Metadata')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('Additional Information')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                ])->schema([
                                    DatePicker::make('exit_date'),
                                    TextInput::make('clearance_status')->maxLength(100),
                                ]),
                                TextInput::make('exit_reason')
                                    ->maxLength(255),
                                Textarea::make('notes')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(false),
        ]);
    }

    /*
     * public static function table(Table $table): Table
     * {
     *     return $table
     *         ->columns([
     *             Tables\Columns\ImageColumn::make('avatar_path')
     *                 ->circular(),
     *
     *             Tables\Columns\TextColumn::make('employee_number')
     *                 ->searchable()
     *                 ->sortable(),
     *
     *             Tables\Columns\TextColumn::make('full_name')
     *                 ->searchable()
     *                 ->sortable(),
     *
     *             Tables\Columns\TextColumn::make('department.name')
     *                 ->label('Department')
     *                 ->sortable(),
     *
     *             Tables\Columns\TextColumn::make('jobTitle.name')
     *                 ->label('Job Title')
     *                 ->sortable(),
     *
     *             Tables\Columns\TextColumn::make('phone')
     *                 ->searchable(),
     *
     *             Tables\Columns\TextColumn::make('status')
     *                 ->badge(),
     *
     *             Tables\Columns\IconColumn::make('is_active')
     *                 ->boolean()
     *                 ->label('Active'),
     *
     *             Tables\Columns\TextColumn::make('basic_salary')
     *                 ->money('KES')
     *                 ->sortable(),
     *
     *             Tables\Columns\TextColumn::make('hire_date')
     *                 ->date()
     *                 ->sortable(),
     *         ])
     *         ->filters([
     *             Tables\Filters\SelectFilter::make('status')->options([
     *                 'active' => 'Active',
     *                 'inactive' => 'Inactive',
     *                 'exited' => 'Exited',
     *                 'suspended' => 'Suspended',
     *             ]),
     *
     *             Tables\Filters\SelectFilter::make('department_id')
     *                 ->label('Department')
     *                 ->options(Department::query()->pluck('name', 'id')),
     *         ])
     *         ->actions([
     *             Tables\Actions\EditAction::make(),
     *         ])
     *         ->bulkActions([
     *             Tables\Actions\BulkActionGroup::make([
     *                 Tables\Actions\DeleteBulkAction::make(),
     *             ]),
     *         ]);
     * }
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')
                    ->circular()
                    ->label('Avatar'),
                Tables\Columns\TextColumn::make('employee_number')
                    ->label('Employee No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobTitle.name')
                    ->label('Job Title')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'warning',
                        'exited' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('basic_salary')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hire_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'exited' => 'Exited',
                    'suspended' => 'Suspended',
                ]),
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(Department::query()->pluck('name', 'id')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit employees')),
                Tables\Actions\Action::make('archive')
                    ->visible(fn($record) =>
                        auth()->user()?->can('delete employees') &&
                        !$record->trashed())
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Archive Employee')
                    ->modalDescription('This employee will be archived and removed from the active list, but not permanently deleted.')
                    ->action(function (Employee $record) {
                        $record->delete();
                    }),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn($record) =>
                        auth()->user()?->can('force delete employees') &&
                        method_exists($record, 'trashed') &&
                        $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Permanently Delete Employee')
                    ->modalDescription('This action cannot be undone. The employee record will be deleted permanently.')
                    ->visible(fn($record) => method_exists($record, 'trashed') && $record->trashed()),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) => method_exists($record, 'trashed') && $record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printEmployees')
                        ->visible(fn() => auth()->user()?->can('export employees'))
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Print Selected Employees')
                        ->modalDescription('Generate a printable PDF report for the selected employees.')
                        ->action(function ($records) {
                            $employees = $records->load(['department', 'jobTitle']);
                            $generatedBy = auth()->user();
                            $generatedByRole = method_exists($generatedBy, 'getRoleNames')
                                ? ($generatedBy->getRoleNames()->first() ?? 'User')
                                : 'User';

                            $qrImage = null;

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                                'pdf.hr.employees-bulk-report',
                                [
                                    'employees' => $employees,
                                    'generatedBy' => $generatedBy,
                                    'generatedByRole' => $generatedByRole,
                                    'qrImage' => $qrImage,
                                ]
                            )->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn() => print ($pdf->output()),
                                'employees-bulk-report-' . now()->format('Ymd_His') . '.pdf'
                            );
                        }),
                    Tables\Actions\BulkAction::make('archiveSelected')
                        ->visible(fn() => auth()->user()?->can('delete employees'))
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Archive Selected Employees')
                        ->modalDescription('Selected employees will be archived and can still be restored later.')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->delete();
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete employees'))
                        ->label('Delete Selected')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Employees')
                        ->modalDescription('This will archive the selected employees instead of deleting them permanently.'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('restore employees')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('force delete employees'))
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Selected Employees')
                        ->modalDescription('This action cannot be undone.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
