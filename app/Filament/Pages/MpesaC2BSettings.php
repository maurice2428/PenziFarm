<?php

namespace App\Filament\Pages;

use App\Models\Sales\MpesaC2BSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MpesaC2BSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $navigationLabel = 'M-Pesa C2B';
    protected static ?string $title = 'M-Pesa C2B';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.mpesa-c2-b-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view mpesa c2b settings')
            || auth()->user()?->hasRole('Administrator');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view mpesa c2b settings')
            || auth()->user()?->hasRole('Administrator');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = MpesaC2BSetting::query()->first();

        $this->form->fill([
            'short_code' => $setting?->short_code,
            'environment' => $setting?->environment ?? 'sandbox',
            'validation_url' => $setting?->validation_url ?? url('/mpesa/c2b/validation'),
            'confirmation_url' => $setting?->confirmation_url ?? url('/mpesa/c2b/confirmation'),
            'response_type' => $setting?->response_type ?? 'Completed',
            'is_active' => $setting?->is_active ?? true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('C2B Register URL Settings')
                    ->description('These URLs receive normal Paybill/Till payments that were not initiated by STK Push.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('short_code')
                            ->label('Paybill / Till Short Code')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\Select::make('environment')
                            ->options([
                                'sandbox' => 'Sandbox',
                                'production' => 'Production',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('validation_url')
                            ->label('Validation URL')
                            ->url()
                            ->required()
                            ->default(url('/mpesa/c2b/validation'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('confirmation_url')
                            ->label('Confirmation URL')
                            ->url()
                            ->required()
                            ->default(url('/mpesa/c2b/confirmation'))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('response_type')
                            ->label('Response Type')
                            ->options([
                                'Completed' => 'Completed',
                                'Cancelled' => 'Cancelled',
                            ])
                            ->default('Completed')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()?->can('edit mpesa c2b settings')
                || auth()->user()?->hasRole('Administrator'),
            403
        );

        $data = $this->form->getState();

        MpesaC2BSetting::query()->updateOrCreate(
            ['id' => MpesaC2BSetting::query()->value('id')],
            $data
        );

        Notification::make()
            ->title('C2B settings saved')
            ->success()
            ->send();
    }
}
