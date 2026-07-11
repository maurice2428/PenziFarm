<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditSessionResource\Pages;
use App\Models\AuditSession;
use App\Models\AuditSetting;
use App\Models\User;
use App\Services\Audit\AuditSessionService;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Infolists;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AuditSessionResource extends Resource
{
    protected static ?string $model = AuditSession::class;

    protected static ?string $navigationGroup = 'Audit Logs';

    protected static ?string $navigationLabel = 'Session(s)';

    protected static ?string $modelLabel = 'Audit Session';

    protected static ?string $pluralModelLabel = 'Audit Sessions';

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'system/audit-sessions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view audit logs') ||
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
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('login_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('login_at')
                    ->label('Login')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->icon('heroicon-o-arrow-right-on-rectangle'),
                Tables\Columns\TextColumn::make('actor_label')
                    ->label('User')
                    ->searchable(['user_name', 'user_email'])
                    ->weight('bold')
                    ->icon('heroicon-o-user-circle')
                    ->description(fn(AuditSession $record): string => $record->user_email ?: 'N/A'),
                Tables\Columns\TextColumn::make('effective_status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn(AuditSession $record): string => $record->effective_status_color)
                    ->sortable(query: fn(Builder $query, string $direction): Builder => $query->orderBy('status', $direction)),
                Tables\Columns\TextColumn::make('logout_reason_label')
                    ->label('Close Reason')
                    ->badge()
                    ->color(fn(AuditSession $record): string => match ($record->logout_reason) {
                        'logout' => 'success',
                        'session_expired', 'expired' => 'warning',
                        'forced' => 'danger',
                        'system' => 'info',
                        default => $record->is_expired_open ? 'warning' : 'gray',
                    }),
                Tables\Columns\TextColumn::make('duration_label')
                    ->label('Duration')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('request_count')
                    ->label('Requests')
                    ->numeric()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('event_count')
                    ->label('Events')
                    ->numeric()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('email_status_label')
                    ->label('Email')
                    ->badge()
                    ->color(fn(AuditSession $record): string => $record->emailed_at ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('available')
                    ->label('Available Sessions')
                    ->query(fn(Builder $query): Builder => $query->available()),
                Tables\Filters\Filter::make('expired_open')
                    ->label('Expired But Not Closed')
                    ->query(fn(Builder $query): Builder => $query->expiredOpen()),
                Tables\Filters\Filter::make('closed')
                    ->label('Closed Sessions')
                    ->query(fn(Builder $query): Builder => $query->closed()),
                Tables\Filters\SelectFilter::make('logout_reason')
                    ->label('Close Reason')
                    ->options([
                        'logout' => 'Logout',
                        'session_expired' => 'Session Expired',
                        'expired' => 'Expired',
                        'forced' => 'Forced',
                        'system' => 'System',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn(): array => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable(),
                Tables\Filters\Filter::make('login_at')
                    ->label('Login Date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From')
                            ->native(false),
                        Forms\Components\DatePicker::make('to')
                            ->label('To')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('login_at', '>=', $date)
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('login_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('printReport')
                    ->label('')
                    ->tooltip('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->color('info')
                    ->openUrlInNewTab()
                    ->url(fn(AuditSession $record): string => route('audit-sessions.report', $record)),
                Tables\Actions\Action::make('viewSession')
                    ->label('')
                    ->tooltip('View session')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->color('gray')
                    ->url(fn(AuditSession $record): string => static::getUrl('view', [
                        'record' => $record,
                    ])),
                Tables\Actions\Action::make('sendEmail')
                    ->label('')
                    ->tooltip('Send audit email')
                    ->icon('heroicon-o-envelope')
                    ->iconButton()
                    ->color('success')
                    ->modalWidth('md')
                    ->modalHeading('Send Audit Session Email')
                    ->modalDescription('Send this audit session summary to an email address.')
                    ->modalCancelAction(false)
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Send To')
                            ->email()
                            ->default(fn(): ?string => app(AuditSessionService::class)->defaultAuditEmail())
                            ->required(),
                    ])
                    ->action(function (AuditSession $record, array $data): void {
                        $sent = app(AuditSessionService::class)
                            ->sendSessionEmail($record, $data['email']);

                        if ($sent) {
                            Notification::make()
                                ->title('Audit session email sent')
                                ->body('The audit summary has been sent successfully.')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Audit session email was not sent')
                            ->body('The session is safe, but email sending failed. Check global email settings.')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('closeNow')
                    ->label('')
                    ->tooltip(fn(AuditSession $record): string => $record->is_expired_open ? 'Close expired session' : 'Close session now')
                    ->icon('heroicon-o-lock-closed')
                    ->iconButton()
                    ->color(fn(AuditSession $record): string => $record->is_expired_open ? 'danger' : 'warning')
                    ->requiresConfirmation()
                    ->modalWidth('md')
                    ->modalHeading('Close Audit Session')
                    ->modalDescription(fn(AuditSession $record): string => $record->is_expired_open
                        ? 'This session has already expired but was not closed. The system will close it now.'
                        : 'This will close the active audit session.')
                    ->modalCancelAction(false)
                    ->visible(fn(AuditSession $record): bool => $record->is_available || $record->is_expired_open)
                    ->action(function (AuditSession $record): void {
                        $sendEmail = (bool) AuditSetting::get(
                            'email_on_logout',
                            filter_var(env('AUDIT_EMAIL_ON_LOGOUT', false), FILTER_VALIDATE_BOOL)
                        );

                        $closed = app(AuditSessionService::class)
                            ->closeSession(
                                session: $record,
                                reason: $record->is_expired_open ? 'session_expired' : 'forced',
                                sendEmail: $sendEmail,
                                destroyLaravelSession: true,
                            );

                        if ($sendEmail && $closed->emailed_at) {
                            Notification::make()
                                ->title('Audit session closed')
                                ->body('The session was closed and the summary email was sent.')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Audit session closed')
                            ->body($sendEmail
                                ? 'The session was closed. Email was not sent because mail configuration failed.'
                                : 'The session was closed. Email sending is disabled.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('closeExpired')
                    ->label('Close Expired Sessions')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Close Expired Audit Sessions')
                    ->modalDescription('This will close selected sessions that are already expired but still marked active.')
                    ->action(function ($records): void {
                        $count = 0;

                        foreach ($records as $record) {
                            if (!$record instanceof AuditSession) {
                                continue;
                            }

                            if (!$record->is_expired_open) {
                                continue;
                            }

                            app(AuditSessionService::class)
                                ->closeSession($record, 'session_expired', false);

                            $count++;
                        }

                        Notification::make()
                            ->title('Expired sessions closed')
                            ->body("Closed {$count} expired session(s).")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Session Summary')
                    ->icon('heroicon-o-finger-print')
                    ->columns(12)
                    ->schema([
                        Infolists\Components\TextEntry::make('actor_label')
                            ->label('User')
                            ->columnSpan(3),
                        Infolists\Components\TextEntry::make('user_email')
                            ->label('Email')
                            ->columnSpan(3),
                        Infolists\Components\TextEntry::make('effective_status_label')
                            ->label('Status')
                            ->badge()
                            ->color(fn(AuditSession $record): string => $record->effective_status_color)
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('logout_reason_label')
                            ->label('Reason')
                            ->badge()
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('duration_label')
                            ->label('Duration')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('login_at')
                            ->label('Login')
                            ->dateTime('d M Y, H:i:s')
                            ->columnSpan(3),
                        Infolists\Components\TextEntry::make('last_seen_at')
                            ->label('Last Seen')
                            ->dateTime('d M Y, H:i:s')
                            ->columnSpan(3),
                        Infolists\Components\TextEntry::make('logout_at')
                            ->label('Closed')
                            ->dateTime('d M Y, H:i:s')
                            ->placeholder('Not closed')
                            ->columnSpan(3),
                        Infolists\Components\TextEntry::make('expires_at')
                            ->label('Expires')
                            ->dateTime('d M Y, H:i:s')
                            ->columnSpan(3),
                        Infolists\Components\TextEntry::make('request_count')
                            ->label('Requests')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('event_count')
                            ->label('Events')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('email_to')
                            ->label('Email Sent To')
                            ->placeholder('Not sent')
                            ->columnSpan(4),
                        Infolists\Components\TextEntry::make('emailed_at')
                            ->label('Email Sent At')
                            ->dateTime('d M Y, H:i:s')
                            ->placeholder('Not sent')
                            ->columnSpan(4),
                        Infolists\Components\TextEntry::make('summary')
                            ->label('Summary')
                            ->placeholder('No summary captured')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('first_url')
                            ->label('First URL')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('last_url')
                            ->label('Last URL')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP')
                            ->placeholder('N/A')
                            ->columnSpan(4),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('Device / Browser')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditSessions::route('/'),
            'view' => Pages\ViewAuditSession::route('/{record}/view'),
        ];
    }
}
