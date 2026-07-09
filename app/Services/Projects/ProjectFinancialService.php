<?php

namespace App\Services\Projects;

use App\Models\Projects\FarmProject;

class ProjectFinancialService
{
    public function recalculate(FarmProject $project): FarmProject
    {
        $budgetAmount = (float) $project->budgetLines()
            ->sum('estimated_amount');

        $approvedBudgetAmount = (float) $project->budgetLines()
            ->sum('approved_amount');

        $spentAmount = (float) $project->expenses()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('total_amount');

        $committedAmount = (float) $project->budgetLines()
            ->whereIn('status', ['approved', 'committed'])
            ->sum('approved_amount');

        $effectiveBudget = $approvedBudgetAmount > 0
            ? $approvedBudgetAmount
            : $budgetAmount;

        $balanceAmount = $effectiveBudget - $spentAmount;
        $varianceAmount = $effectiveBudget - $spentAmount;

        $project->forceFill([
            'budget_amount' => $budgetAmount,
            'approved_budget_amount' => $approvedBudgetAmount,
            'spent_amount' => $spentAmount,
            'committed_amount' => $committedAmount,
            'balance_amount' => $balanceAmount,
            'variance_amount' => $varianceAmount,
        ])->save();

        return $project->refresh();
    }

    public function recalculateProgress(FarmProject $project): FarmProject
    {
        $milestoneCount = $project->milestones()->count();

        if ($milestoneCount > 0) {
            $averageProgress = (int) round($project->milestones()->avg('progress_percent') ?? 0);

            $project->forceFill([
                'progress_percent' => min(100, max(0, $averageProgress)),
            ])->save();

            return $project->refresh();
        }

        $taskCount = $project->tasks()->count();

        if ($taskCount > 0) {
            $averageProgress = (int) round($project->tasks()->avg('progress_percent') ?? 0);

            $project->forceFill([
                'progress_percent' => min(100, max(0, $averageProgress)),
            ])->save();

            return $project->refresh();
        }

        return $project;
    }
}
