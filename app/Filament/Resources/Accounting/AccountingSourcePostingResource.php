<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingSourcePostingResource\Pages;
use App\Models\Accounting\AccountingSourcePosting;
use App\Services\Accounting\AccountingBulkExportService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingSourcePostingResource extends Resource
{
    protected static ?string $model = AccountingSourcePosting::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Accounting Controls';
    protected static ?string $navigationLabel = 'Source Posting Audit';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting source postings') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function form(Form $form): Form { return $form->schema([]); }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at','desc')->columns([
            Tables\Columns\TextColumn::make('posting_key')->limit(55)->tooltip(fn($state):string=>$state)->copyable()->searchable(),
            Tables\Columns\TextColumn::make('source_type')->badge()->searchable(),
            Tables\Columns\TextColumn::make('source_id')->label('Source ID'),
            Tables\Columns\TextColumn::make('source_action')->badge(),
            Tables\Columns\TextColumn::make('source_reference')->searchable()->copyable()->placeholder('-'),
            Tables\Columns\TextColumn::make('journalEntry.journal_number')->label('Journal')->copyable()->placeholder('-'),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn(string $state):string=>match($state){'posted'=>'success','reversed'=>'danger','failed'=>'danger','draft'=>'gray',default=>'warning'}),
            Tables\Columns\TextColumn::make('attempts')->badge(),
            Tables\Columns\TextColumn::make('posted_at')->dateTime('d M Y H:i')->placeholder('-'),
            Tables\Columns\TextColumn::make('last_error')->limit(60)->wrap()->toggleable(isToggledHiddenByDefault:true),
        ])->filters([
            Tables\Filters\SelectFilter::make('source_type')->options(fn():array=>AccountingSourcePosting::query()->distinct()->orderBy('source_type')->pluck('source_type','source_type')->all()),
            Tables\Filters\SelectFilter::make('status')->options(['pending'=>'Pending','draft'=>'Draft','posted'=>'Posted','reversed'=>'Reversed','failed'=>'Failed']),
        ])->actions([
            Tables\Actions\Action::make('openJournal')->label('Journal')->icon('heroicon-o-arrow-top-right-on-square')->url(fn(AccountingSourcePosting $r):string=>$r->journal_entry_id?AccountingJournalEntryResource::getUrl('view',['record'=>$r->journal_entry_id]):'#')->visible(fn(AccountingSourcePosting $r):bool=>filled($r->journal_entry_id)),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn(Collection $records)=>app(AccountingBulkExportService::class)->csv($records,['Posting Key'=>'posting_key','Source Type'=>'source_type','Source ID'=>'source_id','Action'=>'source_action','Reference'=>'source_reference','Journal'=>fn($r)=>$r->journalEntry?->journal_number,'Status'=>'status','Attempts'=>'attempts','Posted At'=>fn($r)=>$r->posted_at?->format('Y-m-d H:i:s'),'Error'=>'last_error'],'source-postings-'.now()->format('Ymd_His').'.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingSourcePostings::route('/')]; }
}
