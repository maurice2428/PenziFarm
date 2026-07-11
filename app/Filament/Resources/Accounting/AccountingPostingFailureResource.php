<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingPostingFailureResource\Pages;
use App\Models\Accounting\AccountingPostingFailure;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingPostingRecoveryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingPostingFailureResource extends Resource
{
    protected static ?string $model = AccountingPostingFailure::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Accounting Controls';
    protected static ?string $navigationLabel = 'Posting Failures';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting posting failures') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return auth()->user()?->can('ignore accounting posting failures') ?? false; }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Posting Failure')->columns(['default'=>1,'md'=>2,'xl'=>4])->schema([
                Forms\Components\TextInput::make('source_type')->readOnly(),
                Forms\Components\TextInput::make('source_id')->readOnly(),
                Forms\Components\TextInput::make('source_action')->readOnly(),
                Forms\Components\TextInput::make('event_name')->readOnly(),
                Forms\Components\TextInput::make('exception_class')->readOnly()->columnSpan(['default'=>1,'xl'=>2]),
                Forms\Components\TextInput::make('status')->readOnly(),
                Forms\Components\TextInput::make('attempts')->readOnly(),
                Forms\Components\DateTimePicker::make('last_attempted_at')->readOnly(),
                Forms\Components\Textarea::make('error_message')->rows(5)->readOnly()->columnSpanFull(),
                Forms\Components\Textarea::make('stack_excerpt')->rows(8)->readOnly()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('last_attempted_at','desc')->columns([
            Tables\Columns\TextColumn::make('source_type')->badge()->searchable(),
            Tables\Columns\TextColumn::make('source_id')->label('Source ID')->searchable(),
            Tables\Columns\TextColumn::make('source_action')->label('Action')->badge(),
            Tables\Columns\TextColumn::make('error_message')->limit(75)->wrap()->searchable(),
            Tables\Columns\TextColumn::make('attempts')->badge(),
            Tables\Columns\TextColumn::make('last_attempted_at')->dateTime('d M Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn(string $state):string=>match($state){'resolved'=>'success','ignored'=>'gray',default=>'danger'}),
            Tables\Columns\TextColumn::make('resolvedBy.name')->label('Resolved By')->placeholder('-')->toggleable(),
            Tables\Columns\TextColumn::make('deleted_at')->dateTime()->label('Archived')->toggleable(isToggledHiddenByDefault:true),
        ])->filters([
            Tables\Filters\SelectFilter::make('source_type')->options(fn():array=>AccountingPostingFailure::query()->distinct()->orderBy('source_type')->pluck('source_type','source_type')->all()),
            Tables\Filters\SelectFilter::make('status')->options(['pending'=>'Pending','resolved'=>'Resolved','ignored'=>'Ignored']),
            Tables\Filters\TrashedFilter::make(),
        ])->actions([
            Tables\Actions\ViewAction::make()->slideOver()->modalWidth('6xl'),
            Tables\Actions\Action::make('retry')->icon('heroicon-o-arrow-path')->color('warning')->requiresConfirmation()->visible(fn(AccountingPostingFailure $r):bool=>$r->status==='pending' && (auth()->user()?->can('retry accounting posting failures') ?? false))->action(function(AccountingPostingFailure $r):void{app(AccountingPostingRecoveryService::class)->retry($r);Notification::make()->success()->title('Posting retried successfully')->send();}),
            Tables\Actions\Action::make('ignore')->icon('heroicon-o-no-symbol')->color('gray')->visible(fn(AccountingPostingFailure $r):bool=>$r->status==='pending' && (auth()->user()?->can('ignore accounting posting failures') ?? false))->form([Forms\Components\Textarea::make('reason')->required()->rows(3)])->action(fn(AccountingPostingFailure $r,array $data)=>app(AccountingPostingRecoveryService::class)->ignore($r,$data['reason'])),
            Tables\Actions\DeleteAction::make()->label('Archive')->visible(fn(AccountingPostingFailure $r):bool=>$r->status!=='pending'),
            Tables\Actions\RestoreAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('retrySelected')->label('Retry Selected')->icon('heroicon-o-arrow-path')->color('warning')->action(function(Collection $records):void{$done=0;$skip=0;foreach($records->where('status','pending') as $r){try{app(AccountingPostingRecoveryService::class)->retry($r);$done++;}catch(\Throwable){$skip++;}}Notification::make()->title("{$done} retried; {$skip} still failed")->color($skip?'warning':'success')->send();})->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('ignoreSelected')->label('Ignore Selected')->icon('heroicon-o-no-symbol')->color('gray')->form([Forms\Components\Textarea::make('reason')->required()->rows(3)])->action(function(Collection $records,array $data):void{foreach($records->where('status','pending') as $r)app(AccountingPostingRecoveryService::class)->ignore($r,$data['reason']);})->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('archiveResolved')->label('Archive Resolved')->icon('heroicon-o-archive-box')->color('danger')->action(fn(Collection $records)=>$records->whereIn('status',['resolved','ignored'])->each->delete())->deselectRecordsAfterCompletion(),
            Tables\Actions\RestoreBulkAction::make(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn(Collection $records)=>app(AccountingBulkExportService::class)->csv($records,['Source Type'=>'source_type','Source ID'=>'source_id','Action'=>'source_action','Error'=>'error_message','Attempts'=>'attempts','Status'=>'status','Last Attempt'=>fn($r)=>$r->last_attempted_at?->format('Y-m-d H:i:s')],'posting-failures-'.now()->format('Ymd_His').'.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingPostingFailures::route('/'),'view'=>Pages\ViewAccountingPostingFailure::route('/{record}')]; }
}
