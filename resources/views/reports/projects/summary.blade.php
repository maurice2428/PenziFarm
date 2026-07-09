@extends('reports.layouts.farm-pdf')

@section('content')
    <table class="metric-table">
        <tr>
            <td class="metric-card">
                <div class="metric-title">Total Projects</div>
                <div class="metric-value">{{ number_format($totals['total_projects']) }}</div>
                <div class="metric-sub">All selected projects</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Approved Budget</div>
                <div class="metric-value">KES {{ number_format($totals['approved_budget'], 2) }}</div>
                <div class="metric-sub">Total approved budget</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Total Spent</div>
                <div class="metric-value">KES {{ number_format($totals['spent'], 2) }}</div>
                <div class="metric-sub">Approved / paid expenses</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Balance</div>
                <div class="metric-value">KES {{ number_format($totals['balance'], 2) }}</div>
                <div class="metric-sub">Remaining budget</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Risk Items</div>
                <div class="metric-value">{{ number_format($totals['over_budget'] + $totals['delayed']) }}</div>
                <div class="metric-sub">Over-budget + delayed</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="11%">Project No.</th>
                <th width="18%">Project</th>
                <th width="10%">Type</th>
                <th width="9%">Status</th>
                <th width="8%">Priority</th>
                <th width="8%">Progress</th>
                <th width="10%">Budget</th>
                <th width="10%">Spent</th>
                <th width="10%">Balance</th>
                <th width="12%">Expected End</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($projects as $index => $project)
                @php
                    $statusClass = match ($project->status) {
                        'completed', 'closed' => 'badge-success',
                        'in_progress' => 'badge-info',
                        'approved' => 'badge-info',
                        'on_hold' => 'badge-warning',
                        'cancelled' => 'badge-danger',
                        default => 'badge-gray',
                    };

                    $priorityClass = match ($project->priority) {
                        'urgent' => 'badge-danger',
                        'high' => 'badge-warning',
                        'medium' => 'badge-info',
                        default => 'badge-gray',
                    };

                    $budget = (float) ($project->approved_budget_amount ?: $project->budget_amount);
                    $spent = (float) $project->spent_amount;
                    $balance = (float) $project->balance_amount;
                    $progress = (int) $project->progress_percent;
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $project->project_number }}</td>
                    <td>
                        <strong>{{ $project->name }}</strong><br>
                        <span class="small-muted">{{ $project->location ?: 'No location' }}</span>
                    </td>
                    <td>{{ str($project->project_type)->replace('_', ' ')->headline() }}</td>
                    <td>
                        <span class="badge {{ $statusClass }}">{{ $project->status_label }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $priorityClass }}">{{ $project->priority_label }}</span>
                    </td>
                    <td>
                        {{ $progress }}%
                        <div class="progress-shell">
                            <div class="progress-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                    </td>
                    <td>KES {{ number_format($budget, 2) }}</td>
                    <td class="{{ $project->is_over_budget ? 'money-danger' : '' }}">
                        KES {{ number_format($spent, 2) }}
                    </td>
                    <td class="{{ $balance < 0 ? 'money-danger' : 'money-positive' }}">
                        KES {{ number_format($balance, 2) }}
                    </td>
                    <td>{{ optional($project->expected_end_date)->format('d M Y') ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
