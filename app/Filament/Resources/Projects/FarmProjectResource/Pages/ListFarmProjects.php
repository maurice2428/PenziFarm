<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\Pages;

use App\Filament\Resources\Projects\FarmProjectResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListFarmProjects extends ListRecords
{
    protected static string $resource = FarmProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('summaryReport')
                ->label('Summary Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(fn(): string => route('projects.reports.summary'))
                ->openUrlInNewTab(),
            Actions\Action::make('budgetVarianceReport')
                ->label('Budget Variance')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->url(fn(): string => route('projects.reports.budget-variance'))
                ->openUrlInNewTab(),
            Actions\Action::make('expensesReport')
                ->label('Expenses Report')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(fn(): string => route('projects.reports.expenses'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make()
                ->label('New Project')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
