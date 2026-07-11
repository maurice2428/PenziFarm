<?php

namespace App\Filament\Pages;


use App\Filament\Pages\AuditDashboard;
use App\Models\AuditSetting;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;

class AuditSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

  protected static ?string $navigationGroup = 'Audit Logs';

protected static ?string $navigationLabel = 'Setting(s)';

protected static ?string $title = 'Audit Settings';

protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

protected static ?int $navigationSort = 4;

protected static ?string $slug = 'system/audit-settings';

protected static string $view = 'filament.pages.audit-settings';

    public ?array $data = [];

    /* public function getHeading(): string|Htmlable
     {
         return '';
     }

     public function getSubheading(): string|Htmlable|null
     {
         return null;
     }*/

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            AuditDashboard::getUrl() => 'System Audit',
            static::getUrl() => 'Audit Settings',
            'Settings',
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view audit settings') ||
            auth()->user()?->can('view audit logs') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_email' => AuditSetting::get('default_email', config('mail.from.address')),
            'high_risk_email' => AuditSetting::get('high_risk_email', config('mail.from.address')),
            'email_on_logout' => AuditSetting::get('email_on_logout', true),
            'email_on_session_expiry' => AuditSetting::get('email_on_session_expiry', true),
            'send_high_risk_alerts' => AuditSetting::get('send_high_risk_alerts', true),
            'send_database_notifications' => AuditSetting::get('send_database_notifications', true),
            'log_page_views' => AuditSetting::get('log_page_views', true),
            'log_livewire_requests' => AuditSetting::get('log_livewire_requests', false),
            'session_lifetime_minutes' => AuditSetting::get('session_lifetime_minutes', config('session.lifetime', 120)),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Audit Email Settings')
                    ->description('Control audit summary recipients, high-risk alerts, and admin notifications.')
                    ->icon('heroicon-o-envelope')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('default_email')
                            ->label('Default Audit Email')
                            ->email()
                            ->required()
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('Session summary emails are sent here.')
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                            ]),
                        Forms\Components\TextInput::make('high_risk_email')
                            ->label('High-Risk Alert Email')
                            ->email()
                            ->required()
                            ->prefixIcon('heroicon-o-shield-exclamation')
                            ->helperText('Sensitive alerts such as deletes, failed logins, and payment changes are sent here.')
                            ->columnSpan([
                                'default' => 12,
                                'md' => 6,
                            ]),
                        Forms\Components\Toggle::make('email_on_logout')
                            ->label('Send Email On Logout')
                            ->default(true)
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                        Forms\Components\Toggle::make('email_on_session_expiry')
                            ->label('Send Email On Session Expiry')
                            ->default(true)
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                        Forms\Components\Toggle::make('send_high_risk_alerts')
                            ->label('Send High-Risk Email Alerts')
                            ->default(true)
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                        Forms\Components\Toggle::make('send_database_notifications')
                            ->label('Send Admin Database Notifications')
                            ->default(true)
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                    ]),
                Forms\Components\Section::make('Tracking Settings')
                    ->description('Control session expiry, admin page tracking, and Livewire request logging.')
                    ->icon('heroicon-o-finger-print')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Toggle::make('log_page_views')
                            ->label('Log Admin Page Views')
                            ->default(true)
                            ->helperText('Records pages visited inside the admin panel.')
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                        Forms\Components\Toggle::make('log_livewire_requests')
                            ->label('Log Livewire Requests')
                            ->default(false)
                            ->helperText('Usually leave this off to avoid too many audit entries.')
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                        Forms\Components\TextInput::make('session_lifetime_minutes')
                            ->label('Session Expiry Minutes')
                            ->numeric()
                            ->minValue(5)
                            ->required()
                            ->prefixIcon('heroicon-o-clock')
                            ->helperText('If no activity is detected after this time, the audit session closes.')
                            ->columnSpan([
                                'default' => 12,
                                'md' => 4,
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AuditSetting::set('default_email', $data['default_email']);
        AuditSetting::set('high_risk_email', $data['high_risk_email']);

        AuditSetting::set('email_on_logout', $data['email_on_logout'], 'boolean');
        AuditSetting::set('email_on_session_expiry', $data['email_on_session_expiry'], 'boolean');
        AuditSetting::set('send_high_risk_alerts', $data['send_high_risk_alerts'], 'boolean');
        AuditSetting::set('send_database_notifications', $data['send_database_notifications'], 'boolean');

        AuditSetting::set('log_page_views', $data['log_page_views'], 'boolean');
        AuditSetting::set('log_livewire_requests', $data['log_livewire_requests'], 'boolean');
        AuditSetting::set('session_lifetime_minutes', $data['session_lifetime_minutes'], 'integer');

        Notification::make()
            ->title('Audit settings saved')
            ->body('Audit email, session, alert, and tracking settings have been updated.')
            ->success()
            ->send();
    }
}
