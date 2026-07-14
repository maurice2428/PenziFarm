<?php

namespace App\Filament\Resources\HR;

use App\Filament\Imports\EmployeeImporter;
use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\EmployeeResource\Pages;
use App\Filament\Resources\HR\EmployeeResource\RelationManagers;
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
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Infolists;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    use HasResourcePermissions;

    protected static string $permissionPrefix = 'employees';

    protected static ?string $model = Employee::class;

    // protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Human Resource';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationBadge(): ?string
    {
        return (string) Employee::query()->where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Personal Information')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Section::make('Employee Identity')
                            ->description('Official identity, contact information, and employee profile photo.')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])->schema([
                                    TextInput::make('employee_number')
                                        ->label('Employee Number')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->placeholder(fn(): string => Employee::employeeNumberPrefix() . '0001')
                                        ->helperText('Generated automatically from the organization name in Global Settings.'),
                                    TextInput::make('first_name')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('middle_name')
                                        ->maxLength(100),
                                    TextInput::make('last_name')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('id_passport_number')
                                        ->label('National ID / Passport No.')
                                        ->required()
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
                                        ->label('SHA No.')
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
                                    TextInput::make('nationality')
                                        ->default('Kenyan')
                                        ->maxLength(80),
                                    DatePicker::make('date_of_birth')
                                        ->label('Date of Birth')
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->maxDate(now())
                                        ->closeOnDateSelection(),
                                    TextInput::make('place_of_birth')
                                        ->label('Place of Birth')
                                        ->maxLength(120),
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
                                Grid::make([
                                    'default' => 1,
                                    'lg' => 3,
                                ])->schema([
                                    FileUpload::make('avatar_path')
                                        ->label('Employee Photo')
                                        ->disk('public')
                                        ->directory('employees/avatars')
                                        ->visibility('public')
                                        ->image()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(3072)
                                        ->imageEditor()
                                        ->avatar()
                                        ->circleCropper()
                                        ->downloadable()
                                        ->openable(),
                                    FileUpload::make('id_document_front_path')
                                        ->label('ID / Passport Front')
                                        ->disk('public')
                                        ->directory('employees/identity-documents')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                        ->maxSize(5120)
                                        ->downloadable()
                                        ->openable(),
                                    FileUpload::make('id_document_back_path')
                                        ->label('ID / Passport Back')
                                        ->disk('public')
                                        ->directory('employees/identity-documents')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                        ->maxSize(5120)
                                        ->downloadable()
                                        ->openable(),
                                ]),
                            ]),
                    ]),
                Wizard\Step::make('Employment')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Section::make('Employment Details')
                            ->description('Placement, reporting line, contract, and employment status.')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])->schema([
                                    DatePicker::make('hire_date')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d M Y'),
                                    Select::make('department_id')
                                        ->label('Department')
                                        ->relationship('department', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    Select::make('job_title_id')
                                        ->label('Job Title')
                                        ->relationship('jobTitle', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    Select::make('reporting_manager_id')
                                        ->label('Reporting Manager')
                                        ->relationship(
                                            name: 'manager',
                                            titleAttribute: 'full_name',
                                            modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true),
                                        )
                                        ->searchable(['full_name', 'employee_number'])
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
                                        ->live()
                                        ->native(false),
                                    TextInput::make('work_station')
                                        ->label('Work Station')
                                        ->maxLength(150),
                                    Select::make('status')
                                        ->required()
                                        ->live()
                                        ->options([
                                            'active' => 'Active',
                                            'on_leave' => 'On Leave',
                                            'suspended' => 'Suspended',
                                            'inactive' => 'Inactive',
                                            'exited' => 'Exited',
                                        ])
                                        ->default('active')
                                        ->native(false)
                                        ->afterStateUpdated(
                                            fn(?string $state, Set $set) => $set('is_active', $state === 'active')
                                        ),
                                    Toggle::make('is_active')
                                        ->label('Currently Active')
                                        ->default(true)
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->helperText('Controlled automatically by employment status.'),
                                    DatePicker::make('contract_start_date')
                                        ->native(false)
                                        ->visible(fn(Get $get): bool => in_array(
                                            $get('employment_type'),
                                            ['contract', 'probation', 'internship'],
                                            true,
                                        )),
                                    DatePicker::make('contract_end_date')
                                        ->native(false)
                                        ->afterOrEqual('contract_start_date')
                                        ->visible(fn(Get $get): bool => in_array(
                                            $get('employment_type'),
                                            ['contract', 'probation', 'internship'],
                                            true,
                                        )),
                                ]),
                            ]),
                    ]),
                Wizard\Step::make('Payroll & Benefits')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Section::make('Salary Details')
                            ->description('Recurring monthly salary components used by payroll.')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])->schema([
                                    TextInput::make('basic_salary')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required()
                                        ->prefix('KES'),
                                    TextInput::make('house_allowance')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->prefix('KES'),
                                    TextInput::make('transport_allowance')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->prefix('KES'),
                                    TextInput::make('other_allowance')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->prefix('KES'),
                                ]),
                                Forms\Components\Placeholder::make('gross_salary_preview')
                                    ->label('Estimated Gross Monthly Salary')
                                    ->content(function (Get $get): string {
                                        $gross = (float) ($get('basic_salary') ?? 0)
                                            + (float) ($get('house_allowance') ?? 0)
                                            + (float) ($get('transport_allowance') ?? 0)
                                            + (float) ($get('other_allowance') ?? 0);

                                        return 'KES ' . number_format($gross, 2);
                                    }),
                            ]),
                        Section::make('Payment Method')
                            ->description('Salary destination and payment account details.')
                            ->icon('heroicon-o-credit-card')
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
                                            'cash' => 'Cash',
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
                                        ->visible(fn(Get $get): bool => $get('payment_method') === 'bank')
                                        ->required(fn(Get $get): bool => $get('payment_method') === 'bank'),
                                    TextInput::make('bank_branch')
                                        ->visible(fn(Get $get): bool => $get('payment_method') === 'bank')
                                        ->required(fn(Get $get): bool => $get('payment_method') === 'bank'),
                                    TextInput::make('account_number')
                                        ->label('Bank Account Number')
                                        ->visible(fn(Get $get): bool => $get('payment_method') === 'bank')
                                        ->required(fn(Get $get): bool => $get('payment_method') === 'bank'),
                                    TextInput::make('mpesa_number')
                                        ->label('Safaricom / M-Pesa Number')
                                        ->tel()
                                        ->placeholder('07XXXXXXXX')
                                        ->visible(fn(Get $get): bool => $get('payment_method') === 'mpesa')
                                        ->required(fn(Get $get): bool => $get('payment_method') === 'mpesa'),
                                    TextInput::make('airtel_money_number')
                                        ->label('Airtel Money Number')
                                        ->tel()
                                        ->placeholder('07XXXXXXXX')
                                        ->visible(fn(Get $get): bool => $get('payment_method') === 'airtel_money')
                                        ->required(fn(Get $get): bool => $get('payment_method') === 'airtel_money'),
                                ]),
                            ]),
                        Section::make('Statutory Payroll Settings')
                            ->description('Enable or disable the statutory computations that apply to this employee.')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 5,
                                ])->schema([
                                    Toggle::make('tax_enabled')->label('Apply PAYE')->default(true),
                                    Toggle::make('is_tax_resident')->label('Kenyan Tax Resident')->default(true),
                                    Toggle::make('nssf_enabled')->label('Apply NSSF')->default(true),
                                    Toggle::make('sha_enabled')->label('Apply SHIF / SHA')->default(true),
                                    Toggle::make('housing_levy_enabled')->label('Apply Housing Levy')->default(true),
                                ]),
                            ]),
                        Section::make('PAYE Allowable Deductions')
                            ->description(
                                'Enter the employee’s supported monthly amounts. Payroll should cap each amount at the applicable statutory limit.'
                            )
                            ->icon('heroicon-o-document-minus')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])->schema([
                                    TextInput::make('registered_pension_contribution')
                                        ->label('Registered Pension / Provident Contribution')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue((float) config('hr.paye.registered_pension_monthly_cap', 30000))
                                        ->default(0)
                                        ->prefix('KES')
                                        ->helperText('Monthly deductible amount; maximum KES 30,000.'),
                                    TextInput::make('post_retirement_medical_contribution')
                                        ->label('Post-Retirement Medical Fund Contribution')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue((float) config('hr.paye.post_retirement_medical_monthly_cap', 15000))
                                        ->default(0)
                                        ->prefix('KES')
                                        ->helperText('Monthly deductible amount; maximum KES 15,000.'),
                                    TextInput::make('mortgage_interest')
                                        ->label('Owner-Occupied Mortgage Interest')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue((float) config('hr.paye.mortgage_interest_monthly_cap', 30000))
                                        ->default(0)
                                        ->prefix('KES')
                                        ->helperText('Monthly deductible amount; maximum KES 30,000 and subject to supporting documents.'),
                                ]),
                            ]),
                        Section::make('PAYE Reliefs & Exemptions')
                            ->description(
                                'Personal relief is applied automatically to eligible resident employees. Insurance relief is calculated from the qualifying monthly premium.'
                            )
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])->schema([
                                    TextInput::make('insurance_premium')
                                        ->label('Qualifying Monthly Insurance Premium')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->prefix('KES')
                                        ->helperText('Enter the documented premium, not the relief amount.'),
                                    Forms\Components\Placeholder::make('insurance_relief_preview')
                                        ->label('Calculated Monthly Insurance Relief')
                                        ->content(function (Get $get): string {
                                            $relief = min(
                                                round(
                                                    ((float) ($get('insurance_premium') ?? 0))
                                                        * (float) config('hr.paye.insurance_relief_rate', 0.15),
                                                    2,
                                                ),
                                                (float) config('hr.paye.insurance_relief_monthly_cap', 5000),
                                            );

                                            return 'KES ' . number_format($relief, 2);
                                        })
                                        ->helperText('15% of qualifying premium, capped at KES 5,000 per month.'),
                                    TextInput::make('tax_exemption_number')
                                        ->label('Tax Exemption Certificate No.')
                                        ->maxLength(80),
                                    DatePicker::make('tax_exemption_expiry')
                                        ->label('Tax Exemption Expiry')
                                        ->native(false),
                                ]),
                                FileUpload::make('tax_supporting_document_path')
                                    ->label('PAYE Supporting Documents')
                                    ->disk('public')
                                    ->directory('employees/tax-supporting-documents')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Upload pension, PRMF, mortgage, insurance, or exemption evidence.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Wizard\Step::make('Exit & Notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('Exit, Clearance & Notes')
                            ->description('Use the formal employment actions on the View page for suspension, demotion, promotion, or termination.')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])->schema([
                                    DatePicker::make('exit_date')->native(false),
                                    Select::make('clearance_status')
                                        ->options([
                                            'not_applicable' => 'Not Applicable',
                                            'pending' => 'Pending',
                                            'in_progress' => 'In Progress',
                                            'cleared' => 'Cleared',
                                            'withheld' => 'Withheld',
                                        ])
                                        ->native(false),
                                    TextInput::make('exit_reason')->maxLength(255),
                                ]),
                                Textarea::make('notes')->rows(6)->columnSpanFull(),
                            ]),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(false),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\ViewEntry::make('employee_identity_card')
                ->label('')
                ->view('filament.infolists.components.employee-identity-card')
                ->columnSpanFull(),
            Infolists\Components\Section::make('Employment Overview')
                ->icon('heroicon-o-briefcase')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Infolists\Components\TextEntry::make('department.name')->label('Department')->placeholder('Not assigned'),
                    Infolists\Components\TextEntry::make('jobTitle.name')->label('Job Title')->placeholder('Not assigned'),
                    Infolists\Components\TextEntry::make('manager.full_name')->label('Reporting Manager')->placeholder('Not assigned'),
                    Infolists\Components\TextEntry::make('work_station')->label('Work Station')->placeholder('Not assigned'),
                    Infolists\Components\TextEntry::make('employment_type')->badge()->formatStateUsing(fn(?string $state): string => $state ? str($state)->headline()->toString() : 'Not set'),
                    Infolists\Components\TextEntry::make('hire_date')->date('d M Y')->placeholder('Not set'),
                    Infolists\Components\TextEntry::make('contract_start_date')->date('d M Y')->placeholder('Not applicable'),
                    Infolists\Components\TextEntry::make('contract_end_date')->date('d M Y')->placeholder('Not applicable'),
                ]),
            Infolists\Components\Section::make('Contact & Compliance')
                ->icon('heroicon-o-shield-check')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Infolists\Components\TextEntry::make('phone')->icon('heroicon-m-phone'),
                    Infolists\Components\TextEntry::make('email')->icon('heroicon-m-envelope')->placeholder('Not provided'),
                    Infolists\Components\TextEntry::make('kra_pin')->label('KRA PIN')->placeholder('Not provided'),
                    Infolists\Components\TextEntry::make('nssf_number')->label('NSSF No.')->placeholder('Not provided'),
                    Infolists\Components\TextEntry::make('nhif_sha_number')->label('SHA No.')->placeholder('Not provided'),
                    Infolists\Components\TextEntry::make('county')->placeholder('Not provided'),
                    Infolists\Components\TextEntry::make('address')->placeholder('Not provided'),
                    Infolists\Components\TextEntry::make('postal_address')->placeholder('Not provided'),
                ]),
            Infolists\Components\Section::make('Payroll Profile')
                ->icon('heroicon-o-banknotes')
                ->visible(fn(): bool => auth()->user()?->can('view employee payroll') ||
                    auth()->user()?->can('edit employees'))
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Infolists\Components\TextEntry::make('basic_salary')->money('KES'),
                    Infolists\Components\TextEntry::make('total_allowance')->label('Total Allowances')->money('KES'),
                    Infolists\Components\TextEntry::make('gross_salary')->label('Gross Monthly Salary')->money('KES'),
                    Infolists\Components\TextEntry::make('payment_method')->badge()->formatStateUsing(fn(?string $state): string => $state ? str($state)->headline()->toString() : 'Not set'),
                    Infolists\Components\TextEntry::make('registered_pension_contribution')->money('KES'),
                    Infolists\Components\TextEntry::make('post_retirement_medical_contribution')->money('KES'),
                    Infolists\Components\TextEntry::make('mortgage_interest')->money('KES'),
                    Infolists\Components\TextEntry::make('insurance_relief')->label('Calculated Insurance Relief')->money('KES'),
                ]),
            Infolists\Components\Section::make('Exit & Clearance')
                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->visible(fn(Employee $record): bool => $record->status === 'exited' || filled($record->exit_date))
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('exit_date')->date('d M Y'),
                    Infolists\Components\TextEntry::make('exit_reason')->columnSpan(2),
                    Infolists\Components\TextEntry::make('clearance_status')->badge(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn(Employee $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name)),
                Tables\Columns\TextColumn::make('employee_number')
                    ->label('Employee No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->description(fn(Employee $record): ?string => $record->jobTitle?->name)
                    ->searchable(['first_name', 'middle_name', 'last_name', 'full_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('work_station')
                    ->label('Station')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => str($state)->headline()->toString())
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'on_leave' => 'info',
                        'inactive' => 'gray',
                        'suspended' => 'warning',
                        'exited' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('hire_date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('full_name')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'active' => 'Active',
                        'on_leave' => 'On Leave',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                        'exited' => 'Exited',
                    ]),
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(Department::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('job_title_id')
                    ->label('Job Title')
                    ->options(JobTitle::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            /* ->headerActions([
                 Tables\Actions\Action::make('downloadTemplate')
                     ->label('Excel Template')
                     ->icon('heroicon-o-arrow-down-tray')
                     ->color('gray')
                     ->url(asset('templates/hr/employee_import_template.xlsx'))
                     ->openUrlInNewTab(),

                 ImportAction::make('importEmployees')
                     ->label('Import Employees')
                     ->icon('heroicon-o-arrow-up-tray')
                     ->importer(EmployeeImporter::class)
                     ->visible(fn (): bool => auth()->user()?->can('import employees')
                         || auth()->user()?->can('create employees')),
             ])*/
            ->headerActions([
                Tables\Actions\Action::make('downloadExcelTemplate')
                    ->label('Excel Template')
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->url(asset('templates/hr/employee_import_template.xlsx'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('downloadCsvTemplate')
                    ->label('CSV Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    //->url(asset('templates/hr/employee_import_template.csv')),
                    ->url(route('hr.employee-import-template.csv')),
                ImportAction::make('importEmployees')
                    ->label('Import Employees (CSV)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->importer(EmployeeImporter::class)
                    ->visible(
                        fn(): bool =>
                            auth()->user()?->can('import employees') ||
                            auth()->user()?->can('create employees')
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(): bool => auth()->user()?->can('edit employees')),
                Tables\Actions\Action::make('archive')
                    ->visible(fn(Employee $record): bool => auth()->user()?->can('delete employees') && !$record->trashed())
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Archive Employee')
                    ->modalDescription('The employee will be removed from the active list but can be restored later.')
                    ->action(fn(Employee $record) => $record->delete()),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn(): bool => auth()->user()?->can('force delete employees'))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()?->can('delete employees')),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()?->can('force delete employees')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MovementsRelationManager::class,
            RelationManagers\DisciplinaryCasesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['department', 'jobTitle', 'manager']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
