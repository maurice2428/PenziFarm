<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Projects\FarmProject;
use App\Models\Projects\ProjectExpense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectReportController extends Controller
{
    public function summary(Request $request)
    {
        $projects = $this->projectQuery($request)
            ->with(['category', 'manager'])
            ->withCount([
                'milestones',
                'tasks',
                'budgetLines',
                'expenses',
                'documents',
            ])
            ->latest('created_at')
            ->get();

        $totals = [
            'total_projects' => $projects->count(),
            'planned' => $projects->where('status', 'planned')->count(),
            'approved' => $projects->where('status', 'approved')->count(),
            'in_progress' => $projects->where('status', 'in_progress')->count(),
            'completed' => $projects->whereIn('status', ['completed', 'closed'])->count(),
            'cancelled' => $projects->where('status', 'cancelled')->count(),
            'approved_budget' => $projects->sum(fn ($project) => (float) $project->approved_budget_amount),
            'estimated_budget' => $projects->sum(fn ($project) => (float) $project->budget_amount),
            'spent' => $projects->sum(fn ($project) => (float) $project->spent_amount),
            'balance' => $projects->sum(fn ($project) => (float) $project->balance_amount),
            'over_budget' => $projects->filter(fn ($project) => $project->is_over_budget)->count(),
            'delayed' => $projects->filter(fn ($project) => $this->isDelayed($project))->count(),
        ];

        return Pdf::loadView('reports.projects.summary', [
            'projects' => $projects,
            'totals' => $totals,
            'filters' => $request->all(),
            'generatedBy' => auth()->user(),
            'generatedByRole' => $this->currentRoleName(),
            'reportTitle' => 'Projects & Works Summary Report',
            'reportSubtitle' => 'Consolidated overview of farm projects, budgets, progress, delays and spending.',
            'recordCount' => $projects->count(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream('projects-summary-report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf');
    }

    public function projectDetail(FarmProject $project)
    {
        $project->load([
            'category',
            'manager',
            'approver',
            'creator',
            'milestones' => fn ($query) => $query->orderBy('target_date'),
            'tasks' => fn ($query) => $query->orderBy('due_date'),
            'budgetLines' => fn ($query) => $query->orderBy('cost_category')->orderBy('item_name'),
            'expenses' => fn ($query) => $query->orderByDesc('expense_date'),
            'progressUpdates' => fn ($query) => $query->orderByDesc('update_date'),
            'documents' => fn ($query) => $query->latest(),
        ]);

        $totals = [
            'budget_lines' => $project->budgetLines->count(),
            'expenses' => $project->expenses->count(),
            'milestones' => $project->milestones->count(),
            'tasks' => $project->tasks->count(),
            'documents' => $project->documents->count(),
            'estimated_budget' => (float) $project->budget_amount,
            'approved_budget' => (float) $project->approved_budget_amount,
            'spent' => (float) $project->spent_amount,
            'balance' => (float) $project->balance_amount,
            'variance' => (float) $project->variance_amount,
        ];

        return Pdf::loadView('reports.projects.detail', [
            'project' => $project,
            'totals' => $totals,
            'generatedBy' => auth()->user(),
            'generatedByRole' => $this->currentRoleName(),
            'reportTitle' => 'Project Detailed Report',
            'reportSubtitle' => $project->project_number . ' • ' . $project->name,
            'recordCount' => 1,
        ])
            ->setPaper('a4', 'landscape')
            ->stream('project-detail-' . Str::slug($project->project_number . '-' . $project->name) . '.pdf');
    }

    public function expenses(Request $request)
    {
        $expenses = ProjectExpense::query()
            ->with(['project.category', 'budgetLine', 'creator', 'approver'])
            ->when($request->filled('project_id'), fn (Builder $query) => $query->where('farm_project_id', $request->project_id))
            ->when($request->filled('expense_type'), fn (Builder $query) => $query->where('expense_type', $request->expense_type))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->status))
            ->when($request->filled('payment_method'), fn (Builder $query) => $query->where('payment_method', $request->payment_method))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('expense_date', '>=', $request->from))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('expense_date', '<=', $request->to))
            ->when($request->filled('project_type'), function (Builder $query) use ($request): Builder {
                return $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('project_type', $request->project_type));
            })
            ->orderByDesc('expense_date')
            ->get();

        $totals = [
            'total_records' => $expenses->count(),
            'total_amount' => $expenses->sum(fn ($expense) => (float) $expense->total_amount),
            'pending_amount' => $expenses->where('status', 'pending')->sum(fn ($expense) => (float) $expense->total_amount),
            'approved_amount' => $expenses->where('status', 'approved')->sum(fn ($expense) => (float) $expense->total_amount),
            'paid_amount' => $expenses->where('status', 'paid')->sum(fn ($expense) => (float) $expense->total_amount),
            'rejected_amount' => $expenses->whereIn('status', ['rejected', 'cancelled'])->sum(fn ($expense) => (float) $expense->total_amount),
        ];

        return Pdf::loadView('reports.projects.expenses', [
            'expenses' => $expenses,
            'totals' => $totals,
            'filters' => $request->all(),
            'generatedBy' => auth()->user(),
            'generatedByRole' => $this->currentRoleName(),
            'reportTitle' => 'Project Expenses Report',
            'reportSubtitle' => 'Detailed spending report for projects and works.',
            'recordCount' => $expenses->count(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream('project-expenses-report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf');
    }

    public function budgetVariance(Request $request)
    {
        $projects = $this->projectQuery($request)
            ->with(['category', 'manager'])
            ->latest('created_at')
            ->get();

        $totals = [
            'total_projects' => $projects->count(),
            'estimated_budget' => $projects->sum(fn ($project) => (float) $project->budget_amount),
            'approved_budget' => $projects->sum(fn ($project) => (float) $project->approved_budget_amount),
            'spent' => $projects->sum(fn ($project) => (float) $project->spent_amount),
            'balance' => $projects->sum(fn ($project) => (float) $project->balance_amount),
            'over_budget' => $projects->filter(fn ($project) => $project->is_over_budget)->count(),
            'delayed' => $projects->filter(fn ($project) => $this->isDelayed($project))->count(),
        ];

        return Pdf::loadView('reports.projects.budget-variance', [
            'projects' => $projects,
            'totals' => $totals,
            'filters' => $request->all(),
            'generatedBy' => auth()->user(),
            'generatedByRole' => $this->currentRoleName(),
            'reportTitle' => 'Project Budget & Variance Report',
            'reportSubtitle' => 'Budget, spent amount, balance, variance, over-budget and delayed project analysis.',
            'recordCount' => $projects->count(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream('project-budget-variance-report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf');
    }

    protected function projectQuery(Request $request): Builder
    {
        return FarmProject::query()
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->status))
            ->when($request->filled('project_type'), fn (Builder $query) => $query->where('project_type', $request->project_type))
            ->when($request->filled('priority'), fn (Builder $query) => $query->where('priority', $request->priority))
            ->when($request->filled('project_category_id'), fn (Builder $query) => $query->where('project_category_id', $request->project_category_id))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('start_date', '>=', $request->from))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('expected_end_date', '<=', $request->to));
    }

    protected function currentRoleName(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'System';
        }

        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->first() ?: 'User';
        }

        return 'User';
    }

    protected function isDelayed(FarmProject $project): bool
    {
        if (! $project->expected_end_date) {
            return false;
        }

        if (in_array($project->status, ['completed', 'closed', 'cancelled'], true)) {
            return false;
        }

        return $project->expected_end_date->lt(now('Africa/Nairobi')->startOfDay());
    }
}
