<?php

namespace App\Filament\Resources\AuditSessionResource\Pages;

use App\Filament\Resources\AuditSessionResource;
use App\Services\Audit\AuditSessionService;
use Filament\Resources\Pages\ListRecords;

class ListAuditSessions extends ListRecords
{
    protected static string $resource = AuditSessionResource::class;

    public function mount(): void
    {
        parent::mount();

        app(AuditSessionService::class)->closeExpiredSessions();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
