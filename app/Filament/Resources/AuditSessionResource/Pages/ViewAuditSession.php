<?php

namespace App\Filament\Resources\AuditSessionResource\Pages;

use App\Filament\Resources\AuditSessionResource;
use App\Services\Audit\AuditSessionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;

class ViewAuditSession extends ViewRecord
{
    protected static string $resource = AuditSessionResource::class;

    protected static string $view = 'filament.resources.audit-session-resource.pages.view-audit-session';

    public function getTitle(): string|Htmlable
    {
        return 'Audit Session';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AuditSessionResource::getUrl('index')),
            Actions\Action::make('printReport')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->openUrlInNewTab()
                ->url(fn(): string => route('audit-sessions.report', $this->record)),
            Actions\Action::make('sendEmail')
                ->label('Send Email')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->modalWidth('md')
                ->modalHeading('Send Audit Session Email')
                ->modalDescription('Send this full audit session summary to the selected email address.')
                ->modalCancelAction(false)
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Send To')
                        ->email()
                        ->default(fn(): ?string => app(AuditSessionService::class)->defaultAuditEmail())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $sent = app(AuditSessionService::class)
                        ->sendSessionEmail($this->record, $data['email']);

                    if ($sent) {
                        Notification::make()
                            ->title('Audit session email sent')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Audit session email failed')
                        ->body('The session was not lost. Check your MAIL settings or use MAIL_MAILER=log locally.')
                        ->warning()
                        ->send();
                }),
            Actions\Action::make('closeNow')
                ->label('Close Session')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->requiresConfirmation()
                ->modalWidth('md')
                ->modalHeading('Close Audit Session')
                ->modalDescription('This closes the audit session and attempts to send the audit summary email.')
                ->modalCancelAction(false)
                ->visible(fn(): bool => $this->record->status === 'active')
                ->action(function (): void {
                    $closed = app(AuditSessionService::class)
                        ->closeSession($this->record, 'forced', true);

                    $this->record = $closed->refresh();

                    Notification::make()
                        ->title('Audit session closed')
                        ->body($closed->emailed_at
                            ? 'The session was closed and the email was sent.'
                            : 'The session was closed, but email was not sent.')
                        ->{$closed->emailed_at ? 'success' : 'warning'}()
                        ->send();
                }),
        ];
    }
}
