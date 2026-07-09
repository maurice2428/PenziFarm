<?php

namespace App\Filament\Pages;

use App\Models\Settings\PaymentSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.payment-settings';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Payment(s) ';

    protected static ?string $title = 'Payment Settings';
     protected static ?int $navigationSort = 3;

   // protected static ? static ?string $navigationGroup = 'System Settings';

    //protected static ?string $navigationLabel = 'Payment Settings';

   // protected static ?string $int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view payment settings'), 403);

        $this->form->fill(PaymentSetting::current()->toArray());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view payment settings') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('PaymentSettingsTabs')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('M-Pesa Daraja')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                Forms\Components\Section::make('Daraja API Credentials')
                                    ->description('Configure Safaricom Daraja credentials for STK Push, callbacks, and customer payment prompts.')
                                    ->icon('heroicon-o-shield-check')
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                    ])
                                    ->schema([
                                        Forms\Components\Select::make('mpesa_environment')
                                            ->label('Environment')
                                            ->options([
                                                'sandbox' => 'Sandbox',
                                                'live' => 'Live',
                                            ])
                                            ->native(false)
                                            ->required()
                                            ->prefixIcon('heroicon-o-globe-alt'),

                                        Forms\Components\Toggle::make('enable_mpesa_stk')
                                            ->label('Enable STK Push')
                                            ->helperText('Allows the system to prompt customers to pay from their phone.')
                                            ->default(false)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check')
                                            ->offIcon('heroicon-m-x-mark'),

                                        Forms\Components\TextInput::make('mpesa_consumer_key')
                                            ->label('Consumer Key')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-key'),

                                        Forms\Components\TextInput::make('mpesa_consumer_secret')
                                            ->label('Consumer Secret')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-lock-closed'),

                                        Forms\Components\TextInput::make('mpesa_shortcode')
                                            ->label('Business Shortcode')
                                            ->placeholder('Example: 174379')
                                            ->maxLength(50)
                                            ->prefixIcon('heroicon-o-building-library'),

                                        Forms\Components\TextInput::make('mpesa_passkey')
                                            ->label('Passkey')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-shield-check'),

                                        Forms\Components\TextInput::make('mpesa_callback_url')
                                            ->label('Callback URL')
                                            ->url()
                                            ->placeholder('https://yourdomain.com/api/mpesa/callback')
                                            ->prefixIcon('heroicon-o-link')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('mpesa_account_reference_prefix')
                                            ->label('Account Reference Prefix')
                                            ->default('LLK')
                                            ->maxLength(20)
                                            ->prefixIcon('heroicon-o-hashtag'),

                                        Forms\Components\TextInput::make('mpesa_transaction_description')
                                            ->label('Transaction Description')
                                            ->default('Lelekwe Farm Payment')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-document-text'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Invoice Payment Details')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Manual M-Pesa / Paybill Display')
                                    ->description('These details appear on invoices when customers pay manually.')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                    ])
                                    ->schema([
                                        Forms\Components\Toggle::make('enable_mpesa_paybill')
                                            ->label('Show M-Pesa Paybill / Till on Invoice')
                                            ->default(true)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check')
                                            ->offIcon('heroicon-m-x-mark'),

                                        Forms\Components\FileUpload::make('mpesa_logo')
                                            ->label('M-Pesa / Safaricom Logo')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('settings/payments/logos')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->downloadable()
                                            ->openable()
                                            ->previewable(),

                                        Forms\Components\TextInput::make('mpesa_paybill_number')
                                            ->label('M-Pesa Paybill Number')
                                            ->maxLength(50)
                                            ->prefixIcon('heroicon-o-credit-card'),

                                        Forms\Components\TextInput::make('mpesa_till_number')
                                            ->label('M-Pesa Till Number')
                                            ->maxLength(50)
                                            ->prefixIcon('heroicon-o-banknotes'),

                                        Forms\Components\TextInput::make('mpesa_account_name')
                                            ->label('M-Pesa Account Name')
                                            ->placeholder('Example: Lelekwe Farm Limited')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-user-circle'),
                                    ]),

                                Forms\Components\Section::make('Accepted Payment Options')
                                    ->description('Control which payment methods are available in the farm ERP.')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->columns([
                                        'default' => 1,
                                        'md' => 3,
                                    ])
                                    ->schema([
                                        Forms\Components\Toggle::make('enable_bank_payment')
                                            ->label('Enable Bank Transfer')
                                            ->default(true)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-building-library')
                                            ->offIcon('heroicon-m-x-mark'),

                                        Forms\Components\Toggle::make('enable_cash_payment')
                                            ->label('Enable Cash Payment')
                                            ->default(true)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-banknotes')
                                            ->offIcon('heroicon-m-x-mark'),

                                        Forms\Components\Toggle::make('enable_cheque_payment')
                                            ->label('Enable Cheque Payment')
                                            ->default(false)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-document-currency-dollar')
                                            ->offIcon('heroicon-m-x-mark'),

                                        Forms\Components\TextInput::make('default_currency')
                                            ->label('Default Currency')
                                            ->default('KES')
                                            ->maxLength(10)
                                            ->prefixIcon('heroicon-o-currency-dollar'),

                                        Forms\Components\TextInput::make('default_tax_rate')
                                            ->label('Default Tax Rate (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->prefixIcon('heroicon-o-calculator'),

                                        Forms\Components\Toggle::make('prices_include_tax')
                                            ->label('Prices Include Tax')
                                            ->default(false)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check')
                                            ->offIcon('heroicon-m-x-mark'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Bank Details')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Forms\Components\Section::make('Bank Payment Details')
                                    ->description('These bank details appear on invoices when customers choose bank transfer.')
                                    ->icon('heroicon-o-building-library')
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 3,
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')
                                            ->label('Bank Name')
                                            ->placeholder('Example: KCB Bank, Equity Bank, NCBA')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-building-library'),

                                        Forms\Components\TextInput::make('bank_branch')
                                            ->label('Branch')
                                            ->placeholder('Example: Nakuru Branch')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-map-pin'),

                                        Forms\Components\TextInput::make('bank_account_name')
                                            ->label('Account Name')
                                            ->placeholder('Example: Lelekwe Farm Limited')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-user-circle'),

                                        Forms\Components\TextInput::make('bank_account_number')
                                            ->label('Account Number')
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-o-credit-card'),

                                        Forms\Components\TextInput::make('bank_swift_code')
                                            ->label('SWIFT Code')
                                            ->maxLength(100)
                                            ->placeholder('Optional')
                                            ->prefixIcon('heroicon-o-globe-alt'),

                                        Forms\Components\TextInput::make('bank_paybill_number')
                                            ->label('Bank Paybill Number')
                                            ->maxLength(100)
                                            ->placeholder('Optional')
                                            ->prefixIcon('heroicon-o-banknotes'),

                                        Forms\Components\TextInput::make('bank_account_reference')
                                            ->label('Payment Reference')
                                            ->placeholder('Example: Use invoice number as account reference')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-hashtag')
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                                'xl' => 2,
                                            ]),

                                        Forms\Components\FileUpload::make('bank_logo')
                                            ->label('Bank Logo')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('settings/payments/banks')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->downloadable()
                                            ->openable()
                                            ->previewable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Invoice Notes & Branding')
                            ->icon('heroicon-o-document-check')
                            ->schema([
                                Forms\Components\Section::make('Invoice & Receipt Notes')
                                    ->description('Default text printed on invoices and receipts.')
                                    ->icon('heroicon-o-document-text')
                                    ->columns(1)
                                    ->schema([
                                        Forms\Components\Textarea::make('invoice_payment_instructions')
                                            ->label('Invoice Payment Instructions')
                                            ->rows(4)
                                            ->placeholder('Example: Please pay using the invoice number as the account reference.'),

                                        Forms\Components\Textarea::make('invoice_footer_note')
                                            ->label('Invoice Footer Note')
                                            ->rows(3)
                                            ->placeholder('Example: Thank you for doing business with Lelekwe Farm.'),

                                        Forms\Components\Textarea::make('receipt_footer_note')
                                            ->label('Receipt Footer Note')
                                            ->rows(3)
                                            ->placeholder('Example: This receipt confirms payment received.'),
                                    ]),

                                Forms\Components\Section::make('Invoice Signature & Stamp')
                                    ->description('These images will appear on generated invoices and payment documents.')
                                    ->icon('heroicon-o-finger-print')
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                    ])
                                    ->schema([
                                        Forms\Components\FileUpload::make('authorized_signature_image')
                                            ->label('Authorized Signature Image')
                                            ->helperText('Recommended: transparent PNG signature.')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('settings/payments/signatures')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->downloadable()
                                            ->openable()
                                            ->previewable()
                                            ->maxSize(4096),

                                        Forms\Components\FileUpload::make('payment_stamp_image')
                                            ->label('Payment Stamp Image')
                                            ->helperText('Recommended: transparent PNG official stamp.')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('settings/payments/stamps')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->downloadable()
                                            ->openable()
                                            ->previewable()
                                            ->maxSize(4096),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('edit payment settings'), 403);

        $settings = PaymentSetting::current();

        $data = $this->form->getState();
        $data['updated_by'] = auth()->id();

        $settings->update($data);

        Notification::make()
            ->title('Payment settings updated successfully')
            ->body('M-Pesa, bank details, invoice notes, stamp, signature, and payment options have been saved.')
            ->success()
            ->send();
    }
}
