<?php

namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use App\Services\HR\EmployeeMovementService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\HtmlString;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return 'Staff Profile';
    }

    public function getSubheading(): HtmlString
    {
        $record = $this->getRecord();

        $text = trim(implode(' • ', array_filter([
            $record->full_name,
            $record->employee_number,
            $record->jobTitle?->name,
            $record->department?->name,
        ])));

        return new HtmlString(
            '<span style="'
            . 'display:inline-block;'
            . 'font-size:0.72rem;'
            . 'font-weight:500;'
            . 'line-height:1.2;'
            . 'letter-spacing:0.01em;'
            . '">'
            . e($text)
            . '</span>'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Profile')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->outlined()
                ->size(ActionSize::Small),

            Actions\ActionGroup::make([
                Actions\Action::make('promote')
                    ->label('Promote Employee')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('success')
                    ->visible(fn (): bool => $this->canPerform('promote employees'))
                    ->form($this->positionChangeForm('Promotion'))
                    ->modalHeading('Promote Employee')
                    ->modalDescription('Record the new position and preserve the previous position in the employee movement history.')
                    ->action(function (array $data, EmployeeMovementService $service): void {
                        $service->changePosition($this->getRecord(), $data, 'promotion');
                        $this->successNotification('Promotion recorded successfully.');
                    }),

                Actions\Action::make('demote')
                    ->label('Demote Employee')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->color('warning')
                    ->visible(fn (): bool => $this->canPerform('demote employees'))
                    ->form($this->positionChangeForm('Demotion'))
                    ->modalHeading('Demote Employee')
                    ->modalDescription('Record the approved position change and its documented reason. The previous position remains in the audit history.')
                    ->action(function (array $data, EmployeeMovementService $service): void {
                        $service->changePosition($this->getRecord(), $data, 'demotion');
                        $this->successNotification('Demotion recorded successfully.');
                    }),

                Actions\Action::make('suspend')
                    ->label('Suspend Employee')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (): bool => $this->getRecord()->status === 'active'
                        && $this->canPerform('suspend employees'))
                    ->form([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Suspension Effective Date')
                            ->default(today())
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('expected_end_date')
                            ->label('Expected End Date')
                            ->afterOrEqual('effective_date')
                            ->native(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Documented Reason')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('supporting_document_path')
                            ->label('Supporting Document')
                            ->disk('public')
                            ->directory('employees/movement-documents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Employee')
                    ->modalDescription('Suspension should be supported by the relevant HR or disciplinary record.')
                    ->action(function (array $data, EmployeeMovementService $service): void {
                        if (filled($data['expected_end_date'] ?? null)) {
                            $data['notes'] = trim(
                                ($data['notes'] ?? '')
                                . PHP_EOL
                                . 'Expected suspension end date: '
                                . $data['expected_end_date']
                            );
                        }

                        $service->changeStatus(
                            $this->getRecord(),
                            $data,
                            'suspension',
                            'suspended'
                        );

                        $this->successNotification('Employee suspended and movement history updated.');
                    }),

                Actions\Action::make('reinstate')
                    ->label('Reinstate Employee')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->visible(fn (): bool => in_array(
                        $this->getRecord()->status,
                        ['suspended', 'inactive', 'exited'],
                        true,
                    ) && $this->canPerform('reinstate employees'))
                    ->form([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Reinstatement Effective Date')
                            ->default(today())
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Documented Reason')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, EmployeeMovementService $service): void {
                        $service->changeStatus(
                            $this->getRecord(),
                            $data,
                            'reinstatement',
                            'active'
                        );

                        $this->successNotification('Employee reinstated successfully.');
                    }),

                Actions\Action::make('terminate')
                    ->label('Terminate Employment')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn (): bool => $this->getRecord()->status !== 'exited'
                        && $this->canPerform('terminate employees'))
                    ->form([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Termination Effective Date')
                            ->default(today())
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('termination_category')
                            ->label('Termination / Exit Category')
                            ->options([
                                'misconduct' => 'Misconduct',
                                'poor_performance' => 'Poor Performance',
                                'incapacity' => 'Incapacity',
                                'redundancy' => 'Redundancy',
                                'end_of_contract' => 'End of Contract',
                                'resignation' => 'Resignation',
                                'retirement' => 'Retirement',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Documented Reason')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notice, hearing, final dues and clearance notes')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('supporting_document_path')
                            ->label('Termination / Exit Document')
                            ->disk('public')
                            ->directory('employees/movement-documents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),

                        Forms\Components\Checkbox::make('process_confirmed')
                            ->label('I confirm that the required notice, hearing, approval and supporting documentation have been completed where applicable.')
                            ->accepted()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Terminate Employment')
                    ->modalDescription('This records the exit and sets clearance to pending. It does not replace the required HR process.')
                    ->action(function (array $data, EmployeeMovementService $service): void {
                        $data['notes'] = trim(
                            'Termination category: '
                            . str($data['termination_category'])->headline()
                            . PHP_EOL
                            . ($data['notes'] ?? '')
                        );

                        $service->changeStatus(
                            $this->getRecord(),
                            $data,
                            'termination',
                            'exited'
                        );

                        $this->successNotification('Employment termination recorded. Clearance is now pending.');
                    }),
            ])
                ->label('Employment Actions')
                ->icon('heroicon-o-briefcase')
                ->color('primary')
                ->button()
                ->size(ActionSize::Small),
        ];
    }

    private function positionChangeForm(string $label): array
    {
        return [
            Forms\Components\DatePicker::make('effective_date')
                ->label("{$label} Effective Date")
                ->default(today())
                ->required()
                ->native(false),

            Forms\Components\Select::make('department_id')
                ->label('New Department')
                ->options(
                    Department::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->default($this->getRecord()->department_id)
                ->searchable()
                ->preload()
                ->native(false),

            Forms\Components\Select::make('job_title_id')
                ->label('New Job Title')
                ->options(
                    JobTitle::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->default($this->getRecord()->job_title_id)
                ->required()
                ->searchable()
                ->preload()
                ->native(false),

            Forms\Components\TextInput::make('basic_salary')
                ->label('New Basic Salary')
                ->numeric()
                ->minValue(0)
                ->prefix('KES')
                ->default($this->getRecord()->basic_salary)
                ->required(),

            Forms\Components\Textarea::make('reason')
                ->label("Reason for {$label}")
                ->required()
                ->rows(4)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('notes')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('supporting_document_path')
                ->label('Approval / Supporting Document')
                ->disk('public')
                ->directory('employees/movement-documents')
                ->acceptedFileTypes([
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])
                ->maxSize(5120)
                ->downloadable()
                ->openable()
                ->columnSpanFull(),
        ];
    }

    private function canPerform(string $permission): bool
    {
        return (bool) (
            auth()->user()?->can($permission)
            || auth()->user()?->can('edit employees')
        );
    }

    private function successNotification(string $message): void
    {
        $this->getRecord()->refresh();

        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }
}
