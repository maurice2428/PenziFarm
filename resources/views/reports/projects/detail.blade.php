@extends('reports.layouts.farm-pdf')

@section('content')
    <table class="metric-table">
        <tr>
            <td class="metric-card">
                <div class="metric-title">Progress</div>
                <div class="metric-value">{{ number_format($project->progress_percent) }}%</div>
                <div class="metric-sub">{{ $project->status_label }}</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Approved Budget</div>
                <div class="metric-value">KES {{ number_format($totals['approved_budget'], 2) }}</div>
                <div class="metric-sub">Project budget</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Spent</div>
                <div class="metric-value">KES {{ number_format($totals['spent'], 2) }}</div>
                <div class="metric-sub">Approved / paid expenses</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Balance</div>
                <div class="metric-value">KES {{ number_format($totals['balance'], 2) }}</div>
                <div class="metric-sub">Remaining budget</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Documents</div>
                <div class="metric-value">{{ number_format($totals['documents']) }}</div>
                <div class="metric-sub">Uploaded files</div>
            </td>
        </tr>
    </table>

    <div class="section-block">
        <div class="section-heading">Project Information</div>

        <table class="two-column-table">
            <tr>
                <td>
                    <div class="label">Project Number</div>
                    <div class="value">{{ $project->project_number }}</div>
                </td>
                <td>
                    <div class="label">Project Name</div>
                    <div class="value">{{ $project->name }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Category</div>
                    <div class="value">{{ $project->category?->name ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Type</div>
                    <div class="value">{{ str($project->project_type)->replace('_', ' ')->headline() }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Location</div>
                    <div class="value">{{ $project->location ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Project Manager</div>
                    <div class="value">{{ $project->manager?->name ?: '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Start Date</div>
                    <div class="value">{{ optional($project->start_date)->format('d M Y') ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Expected End Date</div>
                    <div class="value">{{ optional($project->expected_end_date)->format('d M Y') ?: '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Contractor</div>
                    <div class="value">{{ $project->contractor_name ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Contractor Phone</div>
                    <div class="value">{{ $project->contractor_phone ?: '-' }}</div>
                </td>
            </tr>
        </table>

        <p><strong>Description:</strong> {{ $project->description ?: '-' }}</p>
        <p><strong>Objectives:</strong> {{ $project->objectives ?: '-' }}</p>
        <p><strong>Scope of Work:</strong> {{ $project->scope_of_work ?: '-' }}</p>
    </div>

    <div class="section-block">
        <div class="section-heading">Budget Lines</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="16%">Category</th>
                    <th width="24%">Item</th>
                    <th width="8%">Qty</th>
                    <th width="8%">Unit</th>
                    <th width="12%">Unit Cost</th>
                    <th width="12%">Approved</th>
                    <th width="12%">Actual</th>
                    <th width="12%">Variance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->budgetLines as $index => $line)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ str($line->cost_category)->replace('_', ' ')->headline() }}</td>
                        <td>{{ $line->item_name }}</td>
                        <td>{{ number_format((float) $line->quantity, 2) }}</td>
                        <td>{{ $line->unit ?: '-' }}</td>
                        <td>KES {{ number_format((float) $line->unit_cost, 2) }}</td>
                        <td>KES {{ number_format((float) $line->approved_amount, 2) }}</td>
                        <td>KES {{ number_format((float) $line->actual_amount, 2) }}</td>
                        <td>KES {{ number_format((float) $line->variance_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section-block">
        <div class="section-heading">Expenses</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="10%">Date</th>
                    <th width="12%">Type</th>
                    <th width="18%">Payee</th>
                    <th width="28%">Description</th>
                    <th width="10%">Method</th>
                    <th width="10%">Status</th>
                    <th width="12%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->expenses as $index => $expense)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ optional($expense->expense_date)->format('d M Y') }}</td>
                        <td>{{ str($expense->expense_type)->replace('_', ' ')->headline() }}</td>
                        <td>{{ $expense->payee ?: '-' }}</td>
                        <td>{{ $expense->description ?: '-' }}</td>
                        <td>{{ str($expense->payment_method)->headline() }}</td>
                        <td>{{ str($expense->status)->headline() }}</td>
                        <td>KES {{ number_format((float) $expense->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="section-block">
        <div class="section-heading">Milestones</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="28%">Milestone</th>
                    <th width="14%">Status</th>
                    <th width="12%">Progress</th>
                    <th width="14%">Target Date</th>
                    <th width="14%">Completed</th>
                    <th width="14%">Budget</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->milestones as $index => $milestone)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $milestone->title }}</td>
                        <td>{{ str($milestone->status)->replace('_', ' ')->headline() }}</td>
                        <td>{{ number_format((int) $milestone->progress_percent) }}%</td>
                        <td>{{ optional($milestone->target_date)->format('d M Y') ?: '-' }}</td>
                        <td>{{ optional($milestone->completed_at)->format('d M Y') ?: '-' }}</td>
                        <td>KES {{ number_format((float) $milestone->budget_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section-block">
        <div class="section-heading">Tasks</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="32%">Task</th>
                    <th width="14%">Status</th>
                    <th width="12%">Priority</th>
                    <th width="12%">Progress</th>
                    <th width="13%">Due Date</th>
                    <th width="13%">Assigned To</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->tasks as $index => $task)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $task->title }}</td>
                        <td>{{ str($task->status)->replace('_', ' ')->headline() }}</td>
                        <td>{{ str($task->priority)->headline() }}</td>
                        <td>{{ number_format((int) $task->progress_percent) }}%</td>
                        <td>{{ optional($task->due_date)->format('d M Y') ?: '-' }}</td>
                        <td>{{ $task->assignee?->name ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section-block">
        <div class="section-heading">Progress Updates</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="12%">Date</th>
                    <th width="22%">Title</th>
                    <th width="10%">Progress</th>
                    <th width="26%">Work Done</th>
                    <th width="26%">Blockers / Next Steps</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->progressUpdates as $index => $update)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ optional($update->update_date)->format('d M Y') ?: '-' }}</td>
                        <td>{{ $update->title }}</td>
                        <td>{{ number_format((int) $update->progress_percent) }}%</td>
                        <td>{{ $update->work_done ?: '-' }}</td>
                        <td>
                            <strong>Blockers:</strong> {{ $update->blockers ?: '-' }}<br>
                            <strong>Next:</strong> {{ $update->next_steps ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
