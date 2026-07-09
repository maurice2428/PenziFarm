@extends('reports.layouts.farm-pdf')

@section('content')
    <table class="metric-table">
        <tr>
            <td class="metric-card">
                <div class="metric-title">Projects</div>
                <div class="metric-value">{{ number_format($totals['total_projects']) }}</div>
                <div class="metric-sub">Selected projects</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Estimated Budget</div>
                <div class="metric-value">KES {{ number_format($totals['estimated_budget'], 2) }}</div>
                <div class="metric-sub">Planned budget</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Approved Budget</div>
                <div class="metric-value">KES {{ number_format($totals['approved_budget'], 2) }}</div>
                <div class="metric-sub">Approved allocation</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Spent</div>
                <div class="metric-value">KES {{ number_format($totals['spent'], 2) }}</div>
                <div class="metric-sub">Approved/paid spend</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Over Budget</div>
                <div class="metric-value">{{ number_format($totals['over_budget']) }}</div>
                <div class="metric-sub">Projects above budget</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="12%">Project No.</th>
                <th width="20%">Project</th>
                <th width="9%">Status</th>
                <th width="10%">Estimated</th>
                <th width="10%">Approved</th>
                <th width="10%">Spent</th>
                <th width="10%">Balance</th>
                <th width="8%">Usage</th>
                <th width="9%">Risk</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($projects as $index => $project)
                @php
                    $budget = (float) ($project->approved_budget_amount ?: $project->budget_amount);
                    $spent = (float) $project->spent_amount;
                    $balance = (float) $project->balance_amount;
                    $usage = $budget > 0 ? min(999, round(($spent / $budget) * 100)) : 0;
                    $isOverBudget = $spent > $budget && $budget > 0;

                    $riskClass = $isOverBudget ? 'badge-danger' : ($usage >= 80 ? 'badge-warning' : 'badge-success');
                    $riskLabel = $isOverBudget ? 'Over Budget' : ($usage >= 80 ? 'Watch' : 'Normal');
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $project->project_number }}</td>
                    <td>
                        <strong>{{ $project->name }}</strong><br>
                        <span class="small-muted">{{ $project->location ?: '-' }}</span>
                    </td>
                    <td>{{ $project->status_label }}</td>
                    <td>KES {{ number_format((float) $project->budget_amount, 2) }}</td>
                    <td>KES {{ number_format((float) $project->approved_budget_amount, 2) }}</td>
                    <td class="{{ $isOverBudget ? 'money-danger' : '' }}">
                        KES {{ number_format($spent, 2) }}
                    </td>
                    <td class="{{ $balance < 0 ? 'money-danger' : 'money-positive' }}">
                        KES {{ number_format($balance, 2) }}
                    </td>
                    <td>
                        {{ number_format($usage) }}%
                        <div class="progress-shell">
                            <div class="progress-fill" style="width: {{ min(100, $usage) }}%;"></div>
                        </div>
                    </td>
                    <td><span class="badge {{ $riskClass }}">{{ $riskLabel }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
