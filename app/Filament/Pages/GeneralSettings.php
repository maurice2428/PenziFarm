<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms;

class GeneralSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $navigationLabel = 'General';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.admin.pages.general-settings';

    public ?array $data = [];

    /*
     * public static function shouldRegisterNavigation(): bool
     * {
     *     return auth()->user()?->hasRole('Admin') ?? false;
     * }
     *
     * public static function canAccess(): bool
     * {
     *     return auth()->user()?->hasRole('Admin') ?? false;
     * }
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view general settings') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view general settings') ?? false;
    }

    #[\Livewire\Attributes\On('setLocation')]
    public function setLocation(string $lat, string $lng): void
    {
        $this->data['latitude'] = $lat;
        $this->data['longitude'] = $lng;
    }

    public function mount(SettingsService $settings): void
    {
        $this->form->fill([
            'farm_name' => $settings->get('farm.name'),
            'tagline' => $settings->get('farm.tagline'),
            'phone' => $settings->get('farm.phone'),
            'email' => $settings->get('farm.email'),
            'country' => $settings->get('farm.country', 'Kenya'),
            'county' => $settings->get('farm.county'),
            'kra_pin' => $settings->get('company.kra_pin'),
            'registration_number' => $settings->get('company.registration_number'),
            'tax_office' => $settings->get('company.tax_office'),
            'bank_name' => $settings->get('company.bank_name'),
            'bank_branch' => $settings->get('company.bank_branch'),
            'bank_account_name' => $settings->get('company.bank_account_name'),
            'bank_account_number' => $settings->get('company.bank_account_number'),
            'bank_swift_code' => $settings->get('company.bank_swift_code'),
            'bank_paybill' => $settings->get('company.bank_paybill'),
            'bank_till_number' => $settings->get('company.bank_till_number'),
            'payment_instructions' => $settings->get('company.payment_instructions'),
            'latitude' => $settings->get('farm.lat'),
            'longitude' => $settings->get('farm.lng'),
            'logo_light' => $settings->get('branding.logo_light'),
            'logo_dark' => $settings->get('branding.logo_dark'),
            'favicon' => $settings->get('branding.favicon'),
            'primary_color' => $settings->get('theme.primary', '#16a34a'),
            'secondary_color' => $settings->get('theme.secondary', '#14532d'),
            'accent_color' => $settings->get('theme.accent', '#f59e0b'),
            'danger_color' => $settings->get('theme.danger', '#dc2626'),
            'success_color' => $settings->get('theme.success', '#16a34a'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('1. Farm Identity')
                    ->description('Core farm profile used across the ERP, reports, receipts, invoices, and official documents.')
                    ->icon('heroicon-o-home-modern')
                    ->schema([
                        Forms\Components\TextInput::make('farm_name')
                            ->label('Farm / Company Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Select::make('country')
                            ->label('Country')
                            ->options([
                                'Kenya' => 'Kenya',
                                'Uganda' => 'Uganda',
                                'Tanzania' => 'Tanzania',
                            ])
                            ->searchable()
                            ->native(false),
                        Forms\Components\TextInput::make('county')
                            ->label('County / Location')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->extraAttributes([
                        'style' => 'border:2px solid var(--primary-color) !important; border-radius:12px !important; padding:12px !important;',
                    ]),
                Forms\Components\Section::make('2. Company Compliance')
                    ->description('Official company registration and tax information used on formal financial documents.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('kra_pin')
                            ->label('KRA PIN')
                            ->placeholder('Example: P000000000A')
                            ->maxLength(50)
                            ->helperText('Used on invoices, receipts, statements, and official reports.'),
                        Forms\Components\TextInput::make('registration_number')
                            ->label('Company Registration Number')
                            ->placeholder('Optional')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('tax_office')
                            ->label('Tax Office / Station')
                            ->placeholder('Optional')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->extraAttributes([
                        'style' => 'border:2px solid var(--primary-color) !important; border-radius:12px !important; padding:12px !important;',
                    ]),
                /* Forms\Components\Section::make('3. Bank & Payment Details')
                     ->description('Payment details used on invoices, receipts, payment requests, and customer statements.')
                     ->icon('heroicon-o-banknotes')
                     ->schema([
                         Forms\Components\TextInput::make('bank_name')
                             ->label('Bank Name')
                             ->placeholder('Example: KCB Bank Kenya')
                             ->maxLength(255),

                         Forms\Components\TextInput::make('bank_branch')
                             ->label('Bank Branch')
                             ->placeholder('Example: Nakuru Branch')
                             ->maxLength(255),

                         Forms\Components\TextInput::make('bank_account_name')
                             ->label('Account Name')
                             ->placeholder('Example: Lelekwe Farms Limited')
                             ->maxLength(255),

                         Forms\Components\TextInput::make('bank_account_number')
                             ->label('Account Number')
                             ->placeholder('Example: 1234567890')
                             ->maxLength(100)
                             ->copyable(),

                         Forms\Components\TextInput::make('bank_swift_code')
                             ->label('SWIFT Code')
                             ->placeholder('Optional')
                             ->maxLength(50)
                             ->copyable(),

                         Forms\Components\TextInput::make('bank_paybill')
                             ->label('M-PESA Paybill')
                             ->placeholder('Optional')
                             ->maxLength(50)
                             ->copyable(),

                         Forms\Components\TextInput::make('bank_till_number')
                             ->label('M-PESA Till Number')
                             ->placeholder('Optional')
                             ->maxLength(50)
                             ->copyable(),

                         Forms\Components\Textarea::make('payment_instructions')
                             ->label('Payment Instructions')
                             ->placeholder('Example: Please quote invoice number as payment reference.')
                             ->rows(3)
                             ->columnSpanFull(),
                     ])
                     ->columns(2)
                     ->extraAttributes([
                         'style' => 'border:2px solid var(--primary-color) !important; border-radius:12px !important; padding:12px !important;',
                     ]),*
                Forms\Components\Section::make('3. Bank & Payment Details')
                    ->description('Payment details used on invoices, receipts, payment requests, and customer statements.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->placeholder('Example: KCB Bank Kenya')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_branch')
                            ->label('Bank Branch')
                            ->placeholder('Example: Nakuru Branch')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_account_name')
                            ->label('Account Name')
                            ->placeholder('Example: Lelekwe Farms Limited')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->placeholder('Example: 1234567890')
                            ->maxLength(100)
                            ->suffixIcon('heroicon-o-credit-card')
                            ->helperText('Official receiving account used for payments.'),
                        Forms\Components\TextInput::make('bank_swift_code')
                            ->label('SWIFT Code')
                            ->placeholder('Optional')
                            ->maxLength(50)
                            ->suffixIcon('heroicon-o-globe-alt'),
                        Forms\Components\TextInput::make('bank_paybill')
                            ->label('M-PESA Paybill')
                            ->placeholder('Optional')
                            ->maxLength(50)
                            ->suffixIcon('heroicon-o-device-phone-mobile'),
                        Forms\Components\TextInput::make('bank_till_number')
                            ->label('M-PESA Till Number')
                            ->placeholder('Optional')
                            ->maxLength(50)
                            ->suffixIcon('heroicon-o-device-phone-mobile'),
                        Forms\Components\Textarea::make('payment_instructions')
                            ->label('Payment Instructions')
                            ->placeholder('Example: Please quote invoice number as payment reference.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->extraAttributes([
                        'style' => '
            border:2px solid var(--primary-color) !important;
            border-radius:12px !important;
            padding:12px !important;
        ',
                    ]),*/
                Forms\Components\Section::make('3. Farm Location')
                    ->description('Geographical details used for maps, directions, reports, and operational context.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->live(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->live(),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('getLocation')
                                ->label('📍 Use My Location')
                                ->action(fn($livewire) => $livewire->dispatch('getLocationFromBrowser')),
                        ]),
                    ])
                    ->columns(2)
                    ->extraAttributes([
                        'style' => 'border:2px solid var(--primary-color) !important; border-radius:12px !important; padding:12px !important;',
                    ]),
                Forms\Components\ViewField::make('map_picker')
                    ->view('filament.admin.components.settings-map'),
                Forms\Components\Section::make('4. Branding')
                    ->description('Visual identity used on the admin panel, reports, invoices, and receipts.')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_light')
                            ->label('Light Logo')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->visibility('public')
                            ->multiple(false)
                            ->openable()
                            ->downloadable(),
                        Forms\Components\FileUpload::make('logo_dark')
                            ->label('Dark Logo')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->visibility('public')
                            ->multiple(false)
                            ->openable()
                            ->downloadable(),
                        Forms\Components\FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->visibility('public')
                            ->multiple(false)
                            ->openable()
                            ->downloadable(),
                    ])
                    ->columns(3)
                    ->extraAttributes([
                        'style' => 'border:2px solid var(--primary-color) !important; border-radius:12px !important; padding:12px !important;',
                    ]),
                Forms\Components\Section::make('5. Theme Colors')
                    ->description('Global colors used across the ERP interface and generated PDF documents.')
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary Color'),
                        Forms\Components\ColorPicker::make('secondary_color')
                            ->label('Secondary Color'),
                        Forms\Components\ColorPicker::make('accent_color')
                            ->label('Accent Color'),
                        Forms\Components\ColorPicker::make('danger_color')
                            ->label('Danger Color'),
                        Forms\Components\ColorPicker::make('success_color')
                            ->label('Success Color'),
                    ])
                    ->columns(3)
                    ->extraAttributes([
                        'style' => 'border:2px solid var(--primary-color) !important; border-radius:12px !important; padding:12px !important;',
                    ]),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        abort_unless(auth()->user()?->can('edit general settings'), 403);

        $data = $this->form->getState();

        $settings->setMany([
            'farm.name' => $data['farm_name'] ?? null,
            'farm.tagline' => $data['tagline'] ?? null,
            'farm.phone' => $data['phone'] ?? null,
            'farm.email' => $data['email'] ?? null,
            'farm.country' => $data['country'] ?? null,
            'farm.county' => $data['county'] ?? null,
            'company.kra_pin' => $data['kra_pin'] ?? null,
            'company.registration_number' => $data['registration_number'] ?? null,
            'company.tax_office' => $data['tax_office'] ?? null,
            'company.bank_name' => $data['bank_name'] ?? null,
            'company.bank_branch' => $data['bank_branch'] ?? null,
            'company.bank_account_name' => $data['bank_account_name'] ?? null,
            'company.bank_account_number' => $data['bank_account_number'] ?? null,
            'company.bank_swift_code' => $data['bank_swift_code'] ?? null,
            'company.bank_paybill' => $data['bank_paybill'] ?? null,
            'company.bank_till_number' => $data['bank_till_number'] ?? null,
            'company.payment_instructions' => $data['payment_instructions'] ?? null,
            'farm.lat' => $data['latitude'] ?? null,
            'farm.lng' => $data['longitude'] ?? null,
            'branding.logo_light' => $data['logo_light'] ?? null,
            'branding.logo_dark' => $data['logo_dark'] ?? null,
            'branding.favicon' => $data['favicon'] ?? null,
            'theme.primary' => trim($data['primary_color'] ?? '#16a34a'),
            'theme.secondary' => trim($data['secondary_color'] ?? '#14532d'),
            'theme.accent' => trim($data['accent_color'] ?? '#f59e0b'),
            'theme.danger' => trim($data['danger_color'] ?? '#dc2626'),
            'theme.success' => trim($data['success_color'] ?? '#16a34a'),
        ], auth()->id());

        Notification::make()
            ->title('General settings saved')
            ->body('Company profile, payment details, branding, and theme settings were updated successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->action('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => auth()->user()?->can('edit general settings')),
        ];
    }
}
