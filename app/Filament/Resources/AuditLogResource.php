<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Infolists;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationGroup = 'Audit Logs';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Logs';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'system/audit-logs';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view audit logs') ||
            auth()->user()?->can('view audit dashboard') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete audit logs') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('15s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Str::headline($state ?: 'event'))
                    ->color(fn(?string $state): string => match ($state) {
                        'created', 'login', 'restored' => 'success',
                        'updated', 'page_view', 'printed', 'exported', 'manual_test' => 'info',
                        'deleted', 'failed_login', 'rejected', 'cancelled' => 'warning',
                        'force_deleted', 'failed_request' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('module')
                    ->label('Module')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actor_label')
                    ->label('User')
                    ->icon('heroicon-o-user-circle')
                    ->searchable(['user_name', 'user_email'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('user_name', $direction);
                    })
                    ->limit(28),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\TextColumn::make('record_label')
                    ->label('Record')
                    ->searchable()
                    ->limit(35)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Str::headline($state ?: 'info'))
                    ->color(fn(?string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        'info' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('route_name')
                    ->label('Route')
                    ->searchable()
                    ->limit(45)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('http_method')
                    ->label('HTTP')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('response_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state): string => match (true) {
                        blank($state) => 'gray',
                        (int) $state >= 500 => 'danger',
                        (int) $state >= 400 => 'warning',
                        (int) $state >= 300 => 'info',
                        default => 'success',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options(fn(): array => AuditLog::query()
                        ->whereNotNull('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event', 'event')
                        ->mapWithKeys(fn($value, $key) => [$key => Str::headline($value)])
                        ->all()),
                Tables\Filters\SelectFilter::make('module')
                    ->label('Module')
                    ->options(fn(): array => AuditLog::query()
                        ->whereNotNull('module')
                        ->distinct()
                        ->orderBy('module')
                        ->pluck('module', 'module')
                        ->all()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                /* Tables\Actions\ViewAction::make()
                     ->label('')
                     ->tooltip('View audit log')
                     ->icon('heroicon-o-eye')
                     ->iconButton()
                     ->color('gray')
                     ->modalWidth('5xl')
                     ->modalSubmitAction(false)
                     ->modalCancelAction(false),*/
                Tables\Actions\Action::make('viewLog')
                    ->label('')
                    ->tooltip('View audit log')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->color('gray')
                    ->url(fn(AuditLog $record): string => static::getUrl('view', [
                        'record' => $record,
                    ])),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete audit log')
                    ->iconButton()
                    ->visible(fn(): bool => auth()->user()?->can('delete audit logs') ||
                        auth()->user()?->hasRole('Admin') ||
                        auth()->user()?->hasRole('Administrator')),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateHeading('No audit logs found')
            ->emptyStateDescription('System activity will appear here once users start interacting with the ERP.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Audit Event Summary')
                    ->icon('heroicon-o-shield-check')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('event')
                            ->label('Event')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => Str::headline($state ?: 'event'))
                            ->color(fn(?string $state): string => match ($state) {
                                'created', 'login', 'restored' => 'success',
                                'updated', 'page_view', 'printed', 'exported', 'manual_test' => 'info',
                                'deleted', 'failed_login', 'rejected', 'cancelled' => 'warning',
                                'force_deleted', 'failed_request' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('module')
                            ->label('Module')
                            ->badge()
                            ->color('gray')
                            ->placeholder('System'),
                        Infolists\Components\TextEntry::make('severity')
                            ->label('Severity')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => Str::headline($state ?: 'info'))
                            ->color(fn(?string $state): string => match ($state) {
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                'info' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d M Y, H:i:s'),
                    ]),
                Infolists\Components\Section::make('User & Record')
                    ->icon('heroicon-o-user-circle')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('actor_label')
                            ->label('User')
                            ->weight(FontWeight::Bold)
                            ->placeholder('System'),
                        Infolists\Components\TextEntry::make('user_email')
                            ->label('Email')
                            ->placeholder('N/A')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('record_display')
                            ->label('Record')
                            ->placeholder('N/A')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('auditable_type')
                            ->label('Model')
                            ->formatStateUsing(fn(?string $state): string => $state ? class_basename($state) : 'N/A'),
                    ]),
                Infolists\Components\Section::make('Description')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->hiddenLabel()
                            ->placeholder('No description captured.')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Request Trace')
                    ->icon('heroicon-o-globe-alt')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('N/A')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('http_method')
                            ->label('HTTP Method')
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('response_status')
                            ->label('Response Status')
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('route_name')
                            ->label('Route')
                            ->placeholder('N/A')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('url')
                            ->label('URL')
                            ->placeholder('N/A')
                            ->copyable()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('Device / Browser')
                            ->placeholder('N/A')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                /* Infolists\Components\Section::make('Changed Data / Metadata')
                     ->icon('heroicon-o-code-bracket-square')
                     ->columns([
                         'default' => 1,
                         'xl' => 3,
                     ])

                     ->schema([
                         Infolists\Components\TextEntry::make('old_values')
                             ->label('Old Values')
                             ->formatStateUsing(fn($state): string => self::formatJsonForDisplay($state))
                             ->placeholder('{}')
                             ->copyable()
                             ->extraAttributes([
                                 'class' => 'font-mono text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-950 text-green-200 p-4 max-h-72 overflow-y-auto',
                             ]),
                         Infolists\Components\TextEntry::make('new_values')
                             ->label('New Values')
                             ->formatStateUsing(fn($state): string => self::formatJsonForDisplay($state))
                             ->placeholder('{}')
                             ->copyable()
                             ->extraAttributes([
                                 'class' => 'font-mono text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-950 text-sky-200 p-4 max-h-72 overflow-y-auto',
                             ]),
                         Infolists\Components\TextEntry::make('metadata')
                             ->label('Metadata')
                             ->formatStateUsing(fn($state): string => self::formatJsonForDisplay($state))
                             ->placeholder('{}')
                             ->copyable()
                             ->extraAttributes([
                                 'class' => 'font-mono text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-950 text-amber-200 p-4 max-h-72 overflow-y-auto',
                             ]),
                     ]),*/
                Infolists\Components\Section::make('Changed Data / Metadata')
                    ->icon('heroicon-o-code-bracket-square')
                    ->columns([
                        'default' => 1,
                        'xl' => 3,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('old_values_display')
                            ->label('Old Values')
                            ->placeholder('{}')
                            ->copyable()
                            ->extraAttributes([
                                'class' => 'font-mono text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-950 text-green-200 p-4 max-h-72 overflow-y-auto',
                            ]),
                        Infolists\Components\TextEntry::make('new_values_display')
                            ->label('New Values')
                            ->placeholder('{}')
                            ->copyable()
                            ->extraAttributes([
                                'class' => 'font-mono text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-950 text-sky-200 p-4 max-h-72 overflow-y-auto',
                            ]),
                        Infolists\Components\TextEntry::make('metadata_display')
                            ->label('Metadata')
                            ->placeholder('{}')
                            ->copyable()
                            ->extraAttributes([
                                'class' => 'font-mono text-xs whitespace-pre-wrap break-words rounded-xl bg-gray-950 text-amber-200 p-4 max-h-72 overflow-y-auto',
                            ]),
                    ]),
            ]);
    }

    protected static function formatJsonForDisplay($state): string
    {
        if (blank($state)) {
            return '{}';
        }

        if (is_string($state)) {
            $decoded = json_decode($state, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            return $state;
        }

        if (is_array($state)) {
            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        }

        return (string) $state;
    }

    /* public static function getPages(): array
     {
         return [
             'index' => Pages\ListAuditLogs::route('/'),
         ];
     }*/
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}/view'),
        ];
    }
}
