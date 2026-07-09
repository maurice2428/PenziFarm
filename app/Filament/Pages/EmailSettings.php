<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $navigationLabel = 'Email ';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.admin.pages.email-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view email settings') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view email settings') ?? false;
    }

    public function mount(SettingsService $settings): void
    {
        $this->form->fill([
            'smtp_host' => $settings->get('mail.host'),
            'smtp_port' => $settings->get('mail.port', 587),
            'smtp_encryption' => $settings->get('mail.encryption', 'tls'),
            'smtp_username' => $settings->get('mail.username'),
            'smtp_password' => $settings->get('mail.password'),
            'smtp_sender_name' => $settings->get('mail.from_name'),
            'smtp_sender_email' => $settings->get('mail.from_address'),
            'test_recipient_email' => $settings->get('mail.from_address'),
        ]);
    }

    /*
     * public function form(Form $form): Form
     * {
     *     return $form
     *         ->schema([
     *             Forms\Components\Section::make('SMTP Server Configuration')
     *                 ->description('Configure the mail server used by the ERP to send invoices, receipts, alerts, and system emails.')
     *                 ->icon('heroicon-o-server')
     *                 ->columns(3)
     *                 ->schema([
     *                     Forms\Components\TextInput::make('smtp_host')
     *                         ->label('SMTP Host')
     *                         ->placeholder('smtp.zoho.com')
     *                         ->required()
     *                         ->maxLength(255),
     *                     Forms\Components\TextInput::make('smtp_port')
     *                         ->label('SMTP Port')
     *                         ->numeric()
     *                         ->placeholder('587')
     *                         ->required(),
     *                     Forms\Components\Select::make('smtp_encryption')
     *                         ->label('Encryption')
     *                         ->options([
     *                             'tls' => 'TLS',
     *                             'ssl' => 'SSL',
     *                             'none' => 'None',
     *                         ])
     *                         ->native(false)
     *                         ->required()
     *                         ->default('tls'),
     *                     Forms\Components\TextInput::make('smtp_username')
     *                         ->label('SMTP Username')
     *                         ->placeholder('email@yourdomain.com')
     *                         ->maxLength(255),
     *                     Forms\Components\TextInput::make('smtp_password')
     *                         ->label('SMTP Password')
     *                         ->password()
     *                         ->revealable()
     *                         ->maxLength(255)
     *                         ->helperText('Use an app password if your mail provider requires one.'),
     *                     Forms\Components\TextInput::make('smtp_sender_email')
     *                         ->label('Sender Email')
     *                         ->email()
     *                         ->placeholder('noreply@yourdomain.com')
     *                         ->required()
     *                         ->maxLength(255),
     *                     Forms\Components\TextInput::make('smtp_sender_name')
     *                         ->label('Sender Name')
     *                         ->placeholder('Lelekwe Farm ERP')
     *                         ->required()
     *                         ->maxLength(255),
     *                     Forms\Components\TextInput::make('test_recipient_email')
     *                         ->label('Test Recipient Email')
     *                         ->email()
     *                         ->placeholder('your-email@example.com')
     *                         ->helperText('The test email will be sent here.')
     *                         ->columnSpan(2),
     *                 ])
     *                 ->extraAttributes([
     *                     'class' => 'rounded-2xl border border-primary-200 bg-white shadow-sm dark:border-primary-800 dark:bg-gray-900',
     *                 ]),
     *             Forms\Components\Section::make('Recommended SMTP Examples')
     *                 ->description('Use these as a guide when configuring your mail provider.')
     *                 ->icon('heroicon-o-information-circle')
     *                 ->schema([
     *                     Forms\Components\Placeholder::make('smtp_examples')
     *                         ->label('')
     *                         ->content(new \Illuminate\Support\HtmlString('
     *                             <div class="grid gap-4 md:grid-cols-3">
     *                                 <div class="rounded-xl border p-4">
     *                                     <div class="font-bold">Zoho Mail</div>
     *                                     <div>Host: smtp.zoho.com</div>
     *                                     <div>Port: 587</div>
     *                                     <div>Encryption: TLS</div>
     *                                 </div>
     *
     *                                 <div class="rounded-xl border p-4">
     *                                     <div class="font-bold">Gmail Workspace</div>
     *                                     <div>Host: smtp.gmail.com</div>
     *                                     <div>Port: 587</div>
     *                                     <div>Encryption: TLS</div>
     *                                 </div>
     *
     *                                 <div class="rounded-xl border p-4">
     *                                     <div class="font-bold">SSL Option</div>
     *                                     <div>Port: 465</div>
     *                                     <div>Encryption: SSL</div>
     *                                 </div>
     *                             </div>
     *                         ')),
     *                 ]),
     *         ])
     *         ->statePath('data');
     * }
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SMTP Server Configuration')
                    ->description('Configure the mail server used by the ERP to send invoices, receipts, alerts, and system emails.')
                    ->icon('heroicon-o-server')
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('smtp_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.zoho.com')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('smtp_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('587')
                            ->required(),
                        Forms\Components\Select::make('smtp_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                'none' => 'None',
                            ])
                            ->native(false)
                            ->required()
                            ->default('tls'),
                        Forms\Components\TextInput::make('smtp_username')
                            ->label('SMTP Username')
                            ->placeholder('email@yourdomain.com')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('smtp_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Use an app password if your mail provider requires one.'),
                        Forms\Components\TextInput::make('smtp_sender_email')
                            ->label('Sender Email')
                            ->email()
                            ->placeholder('noreply@yourdomain.com')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('smtp_sender_name')
                            ->label('Sender Name')
                            ->placeholder('Lelekwe Farm ERP')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('test_recipient_email')
                            ->label('Test Recipient Email')
                            ->email()
                            ->placeholder('your-email@example.com')
                            ->helperText('The test email will be sent here.')
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-primary-200 bg-white shadow-sm dark:border-primary-800 dark:bg-gray-900 sm:rounded-2xl',
                    ]),
                Forms\Components\Section::make('Recommended SMTP Examples')
                    ->description('Use these as a guide when configuring your mail provider.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('smtp_examples')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                    <div class="font-bold text-gray-900 dark:text-white">Zoho Mail</div>
                                    <div class="mt-2 text-gray-700 dark:text-gray-300">Host: smtp.zoho.com</div>
                                    <div class="text-gray-700 dark:text-gray-300">Port: 587</div>
                                    <div class="text-gray-700 dark:text-gray-300">Encryption: TLS</div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                    <div class="font-bold text-gray-900 dark:text-white">Gmail Workspace</div>
                                    <div class="mt-2 text-gray-700 dark:text-gray-300">Host: smtp.gmail.com</div>
                                    <div class="text-gray-700 dark:text-gray-300">Port: 587</div>
                                    <div class="text-gray-700 dark:text-gray-300">Encryption: TLS</div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                    <div class="font-bold text-gray-900 dark:text-white">SSL Option</div>
                                    <div class="mt-2 text-gray-700 dark:text-gray-300">Port: 465</div>
                                    <div class="text-gray-700 dark:text-gray-300">Encryption: SSL</div>
                                </div>
                            </div>
                        ')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        abort_unless(auth()->user()?->can('edit email settings'), 403);

        $settings->setMany([
            'mail.host' => trim((string) ($this->data['smtp_host'] ?? '')),
            'mail.port' => trim((string) ($this->data['smtp_port'] ?? '')),
            'mail.encryption' => ($this->data['smtp_encryption'] ?? 'tls') === 'none'
                ? null
                : $this->data['smtp_encryption'],
            'mail.username' => trim((string) ($this->data['smtp_username'] ?? '')),
            'mail.password' => $this->data['smtp_password'] ?? null,
            'mail.from_name' => trim((string) ($this->data['smtp_sender_name'] ?? '')),
            'mail.from_address' => trim((string) ($this->data['smtp_sender_email'] ?? '')),
        ], auth()->id());

        Notification::make()
            ->title('Email settings saved')
            ->body('SMTP configuration has been updated successfully.')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        abort_unless(auth()->user()?->can('test email settings'), 403);

        $recipient = trim((string) ($this->data['test_recipient_email'] ?? $this->data['smtp_sender_email'] ?? ''));

        if ($recipient === '') {
            Notification::make()
                ->title('Missing test recipient')
                ->body('Please enter a test recipient email address.')
                ->danger()
                ->send();

            return;
        }

        try {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $this->data['smtp_host'] ?? null);
            Config::set('mail.mailers.smtp.port', $this->data['smtp_port'] ?? null);
            Config::set('mail.mailers.smtp.username', $this->data['smtp_username'] ?? null);
            Config::set('mail.mailers.smtp.password', $this->data['smtp_password'] ?? null);
            Config::set('mail.mailers.smtp.encryption', ($this->data['smtp_encryption'] ?? 'tls') === 'none'
                ? null
                : $this->data['smtp_encryption']);

            Config::set('mail.from.address', $this->data['smtp_sender_email'] ?? null);
            Config::set('mail.from.name', $this->data['smtp_sender_name'] ?? 'Farm Management System');

            Mail::raw(
                "This is a test email from your Lelekwe Farm ERP.\n\nIf you received this email, your SMTP settings are working.",
                function ($message) use ($recipient) {
                    $message
                        ->to($recipient)
                        ->subject('Test Email - Lelekwe Farm ERP');
                }
            );

            Notification::make()
                ->title('Test email sent successfully')
                ->body("A test email was sent to {$recipient}.")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Test email failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn() => auth()->user()?->can('test email settings') ?? false)
                ->action('sendTestEmail'),
            Actions\Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => auth()->user()?->can('edit email settings') ?? false)
                ->action('save'),
        ];
    }
}
