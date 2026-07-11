<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingTaxTransactionResource\Pages;
use App\Models\Accounting\AccountingTaxTransaction;
use App\Services\Accounting\AccountingBulkExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AccountingTaxTransactionResource extends Resource
{
    protected static ?string $model = AccountingTaxTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Kenya Tax & Compliance';
    protected static ?string $navigationLabel = 'Tax Register';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting tax transactions') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return auth()->user()?->can('edit accounting tax transactions') ?? false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tax Evidence')->columns(['default'=>1,'md'=>2,'xl'=>4])->schema([
                Forms\Components\TextInput::make('tax_number')->readOnly(),
                Forms\Components\TextInput::make('tax_code')->readOnly(),
                Forms\Components\TextInput::make('direction')->readOnly(),
                Forms\Components\TextInput::make('tax_rate')->suffix('%')->readOnly(),
                Forms\Components\TextInput::make('taxable_amount')->prefix('KES')->readOnly(),
                Forms\Components\TextInput::make('tax_amount')->prefix('KES')->readOnly(),
                Forms\Components\TextInput::make('gross_amount')->prefix('KES')->readOnly(),
                Forms\Components\TextInput::make('party_name')->readOnly(),
                Forms\Components\TextInput::make('party_pin')->readOnly(),
                Forms\Components\TextInput::make('certificate_number'),
                Forms\Components\TextInput::make('etims_invoice_number'),
                Forms\Components\TextInput::make('etims_control_unit'),
                Forms\Components\Select::make('status')->native(false)->options(['posted'=>'Posted','due'=>'Due','filed'=>'Filed','paid'=>'Paid','reversed'=>'Reversed'])->required(),
                Forms\Components\DateTimePicker::make('paid_at')->native(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('transaction_date','desc')->columns([
            Tables\Columns\TextColumn::make('tax_number')->searchable()->copyable()->weight('bold'),
            Tables\Columns\TextColumn::make('transaction_date')->date('d M Y')->sortable(),
            Tables\Columns\TextColumn::make('tax_code')->badge()->searchable(),
            Tables\Columns\TextColumn::make('direction')->badge(),
            Tables\Columns\TextColumn::make('party_name')->searchable()->placeholder('-'),
            Tables\Columns\TextColumn::make('party_pin')->label('PIN')->copyable()->placeholder('-'),
            Tables\Columns\TextColumn::make('taxable_amount')->money('KES')->alignEnd(),
            Tables\Columns\TextColumn::make('tax_amount')->money('KES')->alignEnd(),
            Tables\Columns\TextColumn::make('due_date')->date('d M Y')->color(fn($state,$r):string=>$state&&$state->isPast()&&!in_array($r->status,['paid','filed','reversed'],true)?'danger':'gray'),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn(string $state):string=>match($state){'paid','filed'=>'success','reversed'=>'danger','due'=>'warning',default=>'gray'}),
            Tables\Columns\TextColumn::make('etims_invoice_number')->label('eTIMS')->copyable()->placeholder('-')->toggleable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('tax_code')->options(fn():array=>AccountingTaxTransaction::query()->distinct()->orderBy('tax_code')->pluck('tax_code','tax_code')->all()),
            Tables\Filters\SelectFilter::make('direction')->options(['output'=>'Output Tax','input'=>'Input Tax','withheld'=>'Withheld','payable'=>'Payable','credit'=>'Credit']),
            Tables\Filters\SelectFilter::make('status')->options(['posted'=>'Posted','due'=>'Due','filed'=>'Filed','paid'=>'Paid','reversed'=>'Reversed']),
            Tables\Filters\Filter::make('due')->query(fn(Builder $q):Builder=>$q->whereNotNull('due_date')->whereDate('due_date','<=',now())->whereNotIn('status',['paid','filed','reversed'])),
        ])->actions([Tables\Actions\EditAction::make()->label('Update Evidence')->slideOver()->modalWidth('5xl')])
        ->bulkActions([
            Tables\Actions\BulkAction::make('markFiled')->label('Mark Filed')->icon('heroicon-o-document-check')->color('primary')->action(fn(Collection $records)=>$records->whereNotIn('status',['reversed'])->each->update(['status'=>'filed']))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('markPaid')->label('Mark Paid')->icon('heroicon-o-banknotes')->color('success')->requiresConfirmation()->action(fn(Collection $records)=>$records->whereNotIn('status',['reversed'])->each->update(['status'=>'paid','paid_at'=>now()]))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn(Collection $records)=>app(AccountingBulkExportService::class)->csv($records,['Tax Number'=>'tax_number','Date'=>fn($r)=>$r->transaction_date?->format('Y-m-d'),'Tax Code'=>'tax_code','Direction'=>'direction','Party'=>'party_name','PIN'=>'party_pin','Taxable'=>'taxable_amount','Tax'=>'tax_amount','Gross'=>'gross_amount','Due'=>fn($r)=>$r->due_date?->format('Y-m-d'),'Status'=>'status','eTIMS Invoice'=>'etims_invoice_number'],'tax-register-'.now()->format('Ymd_His').'.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingTaxTransactions::route('/'),'edit'=>Pages\EditAccountingTaxTransaction::route('/{record}/edit')]; }
}
