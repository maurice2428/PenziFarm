<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\AuditSessionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected static string $view = 'filament.resources.audit-log-resource.pages.view-audit-log';

    public function getTitle(): string|Htmlable
    {
        return 'Audit Log';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::SevenExtraLarge;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Logs')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AuditLogResource::getUrl('index')),
            Actions\Action::make('viewSession')
                ->label('View Session')
                ->icon('heroicon-o-finger-print')
                ->color('info')
                ->visible(fn(): bool => filled($this->record->audit_session_id))
                ->url(fn(): string => AuditSessionResource::getUrl('view', [
                    'record' => $this->record->audit_session_id,
                ])),
        ];
    }
}
