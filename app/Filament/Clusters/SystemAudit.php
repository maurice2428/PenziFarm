<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SystemAudit extends Cluster
{
    protected static ?string $navigationLabel = 'System Audit';

    protected static ?string $clusterBreadcrumb = 'System Audit';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'system-audit';
}
