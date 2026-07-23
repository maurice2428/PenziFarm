<?php

namespace App\View\Components;

use App\Services\HrDashboardMetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HrDashboardPanel extends Component
{
    public array $dashboard;

    public string $mode;

    public function __construct(
        HrDashboardMetricsService $metrics,
        string $mode = 'full'
    ) {
        $this->mode = in_array($mode, ['compact', 'full'], true)
            ? $mode
            : 'full';

        $this->dashboard = $metrics->snapshot();
    }

    public function render(): View
    {
        return view('components.hr-dashboard-panel');
    }
}
