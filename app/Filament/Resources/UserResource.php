<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view users') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view users') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create users') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit users') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete users') ?? false;
    }

    public static function permissionTabStateNames(): array
    {
        return [
            'administration',
            'livestock',
            'veterinary',
            'breeding_weights',
            'sales',
            'human_resource',
            'payroll',
            'inventory_reports',
            'asset_valuation_aging',
            'crop_farming',
            'system_audit',
            'projects_works',
            'accounting_finance',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('UserTabs')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('User Details')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Section::make('Basic Details')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('phone')
                                        ->tel()
                                        ->maxLength(30),
                                    Forms\Components\FileUpload::make('avatar')
                                        ->image()
                                        ->directory('avatars')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->imageEditor()
                                        ->avatar()
                                        ->maxSize(5120),
                                    Forms\Components\TextInput::make('password')
                                        ->password()
                                        ->revealable()
                                        ->rule(Password::default())
                                        ->dehydrated(fn(?string $state): bool => filled($state))
                                        ->required(fn(string $operation): bool => $operation === 'create')
                                        ->same('password_confirmation')
                                        ->helperText('Leave blank when editing if you do not want to change password.'),
                                    Forms\Components\TextInput::make('password_confirmation')
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(false)
                                        ->required(fn(string $operation): bool => $operation === 'create'),
                                ])
                                ->columns(2),
                        ]),
                    Forms\Components\Tabs\Tab::make('Roles')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Forms\Components\Section::make('Role Assignment')
                                ->description('Roles allow panel login. Permissions below control actual access.')
                                ->schema([
                                    Forms\Components\Select::make('roles')
                                        ->relationship('roles', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->visible(fn() => auth()->user()?->can('assign roles'))
                                        ->helperText('Assign a role like Veterinary Officer, HR, Finance, etc. Then tick exact permissions below.'),
                                ]),
                        ]),
                    self::permissionTab('Administration', 'heroicon-o-lock-closed', [
                        'view users',
                        'create users',
                        'edit users',
                        'delete users',
                        'assign roles',
                        'assign permissions',
                        'view settings',
                        'edit settings',
                        'view general settings',
                        'edit general settings',
                        'view email settings',
                        'edit email settings',
                        'test email settings',
                        'view payment settings',
                        'edit payment settings',
                        'test mpesa settings',
                        // M-Pesa C2B Settings
                        'view mpesa c2b settings',
                        'edit mpesa c2b settings',
                        'register mpesa c2b urls',
                        'view mpesa c2b transactions',
                        'verify mpesa c2b transactions',
                    ]),
                    self::permissionTab('Livestock', 'heroicon-o-archive-box', [
                        'view animals',
                        'create animals',
                        'edit animals',
                        'delete animals',
                        'archive animals',
                        'restore animals',
                        'export animals',
                        // Livestock Locations
                        'view locations',
                        'create locations',
                        'edit locations',
                        'delete locations',
                        'view sold animals',
                        'view dead culled animals',
                        'view breeds',
                        'create breeds',
                        'edit breeds',
                        'delete breeds',
                        'export breeds',
                        'view animal movement dashboard',
                        'view animal transfers',
                        'create animal transfers',
                        'edit animal transfers',
                        'delete animal transfers',
                        'receive animal transfers',
                        'print animal transfer reports',
                        'view animal groups',
                        'create animal groups',
                        'edit animal groups',
                        'delete animal groups',
                        'sync animal groups',
                        'print animal group reports',
                        'print locations',
                        'export locations',
                    ]),
                    self::permissionTab('Veterinary', 'heroicon-o-heart', [
                        'view health records',
                        'create health records',
                        'edit health records',
                        'delete health records',
                        'export health records',
                        'view animal health records',
                        'create animal health records',
                        'edit animal health records',
                        'delete animal health records',
                        'view clinical cases',
                        'create clinical cases',
                        'edit clinical cases',
                        'delete clinical cases',
                        'view lab requests',
                        'create lab requests',
                        'edit lab requests',
                        'delete lab requests',
                        'view veterinary clinics',
                        'create veterinary clinics',
                        'edit veterinary clinics',
                        'delete veterinary clinics',
                    ]),
                    self::permissionTab('Breeding & Weights', 'heroicon-o-scale', [
                        'delete breeding records',
                        'view weight records',
                        'create weight records',
                        'edit weight records',
                        'delete weight records',
                        'restore weight records',
                        'force delete weight records',
                        'export weight records',
                        'view breeding batches',
                        'create breeding batches',
                        'edit breeding batches',
                        'delete breeding batches',
                        'view breeding records',
                        'edit breeding records',
                        'view gestation rules',
                        'create gestation rules',
                        'edit gestation rules',
                        'delete gestation rules',
                    ]),
                    self::permissionTab('Sales', 'heroicon-o-banknotes', [
                        'view procurement dashboard',
                        'view customer geo intelligence',
                        'print sales gatepasses',
                        'view sales',
                        'create sales',
                        'edit sales',
                        'delete sales',
                        'approve sales',
                        'export sales',
                        'view expenses',
                        'create expenses',
                        'edit expenses',
                        'delete expenses',
                        'approve expenses',
                        'view petty cash',
                        'manage petty cash',
                        'export finance reports',
                        // Income Categories
                        'view income categories',
                        'create income categories',
                        'edit income categories',
                        'delete income categories',
                        'restore income categories',
                        'force delete income categories',
                        'export income categories',
                        'view customers',
                        'create customers',
                        'edit customers',
                        'delete customers',
                        'restore customers',
                        'force delete customers',
                        'export customers',
                        // Sales Invoices
                        'view sales invoices',
                        'create sales invoices',
                        'edit sales invoices',
                        'delete sales invoices',
                        'restore sales invoices',
                        'force delete sales invoices',
                        'approve sales invoices',
                        'cancel sales invoices',
                        'export sales invoices',
                        'print sales invoices',
                        // Sales Payments
                        'view sales payments',
                        'create sales payments',
                        'edit sales payments',
                        'delete sales payments',
                        'restore sales payments',
                        'force delete sales payments',
                        'verify sales payments',
                        'export sales payments',
                        'create purchase order payments',
                        'approve purchase orders',
                        'view supplier statements',
                        'view payment vouchers',
                        'view purchase order payments',
                        'create purchase order payments',
                        'edit purchase order payments',
                        'delete purchase order payments',
                        'print payment vouchers',
                        'view purchase order payments',
                    ]),
                    self::permissionTab('Human Resource', 'heroicon-o-users', [
                        'view hr dashboard',
                        'view departments',
                        'create departments',
                        'edit departments',
                        'delete departments',
                        'view job titles',
                        'create job titles',
                        'edit job titles',
                        'delete job titles',
                        'view holidays',
                        'create holidays',
                        'edit holidays',
                        'delete holidays',
                        'view leave types',
                        'create leave types',
                        'edit leave types',
                        'delete leave types',
                        'view employees',
                        'create employees',
                        'edit employees',
                        'delete employees',
                        'restore employees',
                        'force delete employees',
                        'export employees',
                        'view attendance',
                        'manage attendance',
                        'view leave applications',
                        'create leave applications',
                        'edit leave applications',
                        'delete leave applications',
                        'approve leave applications',
                        'view casual payroll',
                        'create casual payroll',
                        'upload casual payroll',
                        'edit casual payroll',
                        'delete casual payroll',
                        'export casual payroll',
                    ]),
                    self::permissionTab('Payroll', 'heroicon-o-currency-dollar', [
                        'view payroll',
                        'create payroll',
                        'edit payroll',
                        'delete payroll',
                        'generate payroll',
                        'approve payroll',
                        'export payroll',
                        'view payslips',
                        'edit payslips',
                        'delete payslips',
                        'print payslips',
                        'email payslips',
                        'view salary advances',
                        'create salary advances',
                        'edit salary advances',
                        'delete salary advances',
                        'approve salary advances',
                        'export salary advances',
                    ]),
                    self::permissionTab('Inventory & Reports', 'heroicon-o-chart-bar', [
                        'view inventory',
                        'create inventory',
                        'edit inventory',
                        'delete inventory',
                        'manage stock movements',
                        'view reports',
                        'export reports',
                        'print reports',
                        'view inventory items',
                        'create inventory items',
                        'edit inventory items',
                        'delete inventory items',
                        'view stock movements',
                        'create stock movements',
                        'edit stock movements',
                        'delete stock movements',
                        // Suppliers
                        'view suppliers',
                        'create suppliers',
                        'edit suppliers',
                        'delete suppliers',
                        // Health Products
                        'view health products',
                        'create health products',
                        'edit health products',
                        'delete health products',
                        // Inventory Items
                        'view inventory items',
                        'create inventory items',
                        'edit inventory items',
                        'delete inventory items',
                        // Purchase Orders
                        'view purchase orders',
                        'create purchase orders',
                        'edit purchase orders',
                        'delete purchase orders',
                        'receive purchase orders',
                        // Stock Movements
                        'view stock movements',
                        'create stock movements',
                        'edit stock movements',
                        'delete stock movements',
                        // Health Administrations
                        'view health administrations',
                        'create health administrations',
                        'edit health administrations',
                        'delete health administrations',
                        'view goods received notes',
                        'create goods received notes',
                        'print goods received notes',
                        'view animal feedings',
                        'create animal feedings',
                        'print animal feedings',
                        'view stock adjustments',
                        'create stock adjustments',
                        'view stock movements',
                        'print stock movements',
                    ]),
                    self::permissionTab('Asset Valuation & Aging', 'heroicon-o-banknotes', [
                        'view assets',
                        'create assets',
                        'edit assets',
                        'delete assets',
                        'restore assets',
                        'force delete assets',
                        'view asset valuations',
                        'create asset valuations',
                        'edit asset valuations',
                        'delete asset valuations',
                        'view asset maintenance',
                        'create asset maintenance',
                        'edit asset maintenance',
                        'delete asset maintenance',
                        'print asset reports',
                        'print asset valuation report',
                        'export assets',
                    ]),
                    self::permissionTab('Crop Farming', 'heroicon-o-sparkles', [
                        'view crops',
                        'create crops',
                        'edit crops',
                        'delete crops',
                        'view crop fields',
                        'create crop fields',
                        'edit crop fields',
                        'delete crop fields',
                        'view crop seasons',
                        'create crop seasons',
                        'edit crop seasons',
                        'delete crop seasons',
                        'view nursery batches',
                        'create nursery batches',
                        'edit nursery batches',
                        'delete nursery batches',
                        'view crop input applications',
                        'create crop input applications',
                        'delete crop input applications',
                        'view crop harvests',
                        'create crop harvests',
                        'edit crop harvests',
                        'delete crop harvests',
                        'view crop care tasks',
                        'create crop care tasks',
                        'edit crop care tasks',
                        'delete crop care tasks',
                        'print crop reports',
                        'export crop reports',
                    ]),
                    self::permissionTab('System Audit', 'heroicon-o-shield-check', [
                        'view audit logs',
                        'delete audit logs',
                        'export audit logs',
                        'view audit reports',
                        'view audit settings',
                        'edit audit settings',
                        'view audit dashboard',
                        'print audit session reports',
                        'send audit session emails',
                    ]),
                    self::permissionTab('Projects & Works', 'heroicon-o-building-office-2', [
                        'view projects',
                        'create projects',
                        'edit projects',
                        'delete projects',
                        'restore projects',
                        'force delete projects',
                        'view project categories',
                        'create project categories',
                        'edit project categories',
                        'delete project categories',
                        'view project milestones',
                        'create project milestones',
                        'edit project milestones',
                        'delete project milestones',
                        'view project tasks',
                        'create project tasks',
                        'edit project tasks',
                        'delete project tasks',
                        'view project budgets',
                        'create project budgets',
                        'edit project budgets',
                        'delete project budgets',
                        'approve project budgets',
                        'view project expenses',
                        'create project expenses',
                        'edit project expenses',
                        'delete project expenses',
                        'approve project expenses',
                        'view project progress',
                        'create project progress',
                        'edit project progress',
                        'delete project progress',
                        'view project documents',
                        'upload project documents',
                        'delete project documents',
                        'print project reports',
                        'export project reports',
                        'view project dashboard',
                    ]),
                    self::permissionTab('Accounting & Finance', 'heroicon-o-banknotes', [
                        'view accounting dashboard',
                        'view accounting reports',
                        'view trial balance',
                        'view general ledger',
                        'view profit and loss',
                        'view balance sheet',
                        'view cash flow report',
                        'view farm activity explorer',
                        'download accounting pdf reports',
                        'export accounting reports',
                        'view accounting accounts',
                        'create accounting accounts',
                        'edit accounting accounts',
                        'delete accounting accounts',
                        'restore accounting accounts',
                        'force delete accounting accounts',
                        'view accounting fiscal years',
                        'create accounting fiscal years',
                        'edit accounting fiscal years',
                        'delete accounting fiscal years',
                        'restore accounting fiscal years',
                        'force delete accounting fiscal years',
                        'close accounting fiscal years',
                        'reopen accounting fiscal years',
                        'view accounting periods',
                        'create accounting periods',
                        'edit accounting periods',
                        'delete accounting periods',
                        'restore accounting periods',
                        'force delete accounting periods',
                        'close accounting periods',
                        'reopen accounting periods',
                        'view accounting cost centers',
                        'create accounting cost centers',
                        'edit accounting cost centers',
                        'delete accounting cost centers',
                        'restore accounting cost centers',
                        'force delete accounting cost centers',
                        'view accounting journal entries',
                        'create accounting journal entries',
                        'edit accounting journal entries',
                        'delete accounting journal entries',
                        'restore accounting journal entries',
                        'force delete accounting journal entries',
                        'post accounting journal entries',
                        'approve accounting journal entries',
                        'reverse accounting journal entries',
                        'view accounting funding sources',
                        'create accounting funding sources',
                        'edit accounting funding sources',
                        'delete accounting funding sources',
                        'restore accounting funding sources',
                        'force delete accounting funding sources',
                        'view accounting project funds',
                        'create accounting project funds',
                        'edit accounting project funds',
                        'delete accounting project funds',
                        'restore accounting project funds',
                        'force delete accounting project funds',
                        'approve accounting project funds',
                        'view accounting project fund transactions',
                        'create accounting project fund transactions',
                        'edit accounting project fund transactions',
                        'delete accounting project fund transactions',
                        'restore accounting project fund transactions',
                        'force delete accounting project fund transactions',
                        'approve accounting project fund transactions',
                        'view accounting account mappings',
                        'create accounting account mappings',
                        'edit accounting account mappings',
                        'delete accounting account mappings',
                        'restore accounting account mappings',
                        'force delete accounting account mappings',
                        'view accounting opening balances',
                        'create accounting opening balances',
                        'edit accounting opening balances',
                        'delete accounting opening balances',
                        'restore accounting opening balances',
                        'force delete accounting opening balances',
                        'post accounting opening balances',
                        'view accounting tax settings',
                        'create accounting tax settings',
                        'edit accounting tax settings',
                        'delete accounting tax settings',
                        'restore accounting tax settings',
                        'force delete accounting tax settings',
                        'view accounting reconciliations',
                        'create accounting reconciliations',
                        'edit accounting reconciliations',
                        'delete accounting reconciliations',
                        'restore accounting reconciliations',
                        'force delete accounting reconciliations',
                        'approve accounting reconciliations',
                        'complete accounting reconciliations',
                        'run accounting auto posting',
                        'run accounting backfill',
                        'manage accounting module',
                    ]),
                    self::permissionTab('Data Center', 'heroicon-o-server-stack', [
                        'view data center',
                        'run database backups',
                        'schedule database backups',
                        'download database backups',
                        'archive database backups',
                        'restore database backups',
                        'delete database backups',
                        'clear application cache',
                        'clear view cache',
                        'clear route cache',
                        'clear config cache',
                        'clear filament cache',
                        'clear all caches',
                        'view data directories',
                        'create data directories',
                        'edit data directories',
                        'delete data directories',
                        'view data documents',
                        'create data documents',
                        'edit data documents',
                        'delete data documents',
                        'download data documents',
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function permissionTab(
        string $label,
        string $icon,
        array $permissions
    ): Forms\Components\Tabs\Tab {
        $stateName = str($label)->slug('_')->toString();

        return Forms\Components\Tabs\Tab::make($label)
            ->icon($icon)
            ->schema([
                Forms\Components\CheckboxList::make($stateName)
                    ->label($label . ' Permissions')
                    ->options(
                        Permission::query()
                            ->whereIn('name', $permissions)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(2)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (
                        Forms\Components\CheckboxList $component,
                        $state,
                        ?User $record
                    ) use ($permissions) {
                        if (!$record) {
                            return;
                        }

                        $component->state(
                            $record
                                ->permissions()
                                ->whereIn('name', $permissions)
                                ->pluck('id')
                                ->map(fn($id) => (string) $id)
                                ->toArray()
                        );
                    }),
            ]);
    }

    public static function syncPermissions(User $user, array $data): void
    {
        $permissionIds = collect(static::permissionTabStateNames())
            ->flatMap(fn(string $field) => $data[$field] ?? [])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $permissions = Permission::query()
            ->whereIn('id', $permissionIds)
            ->get();

        $user->syncPermissions($permissions);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->disk('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Direct Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Role'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit users')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->can('delete users')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete users')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['roles', 'permissions']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
