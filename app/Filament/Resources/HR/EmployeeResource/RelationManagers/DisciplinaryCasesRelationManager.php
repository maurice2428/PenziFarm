<?php

namespace App\Filament\Resources\HR\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DisciplinaryCasesRelationManager extends RelationManager
{
    protected static string $relationship = 'disciplinaryCases';

    protected static ?string $title = 'Disciplinary & Conduct Cases';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Case Details')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\DatePicker::make('incident_date')->required()->native(false),
                        Forms\Components\Select::make('category')
                            ->options([
                                'attendance' => 'Attendance / Absence',
                                'insubordination' => 'Insubordination',
                                'poor_performance' => 'Poor Performance',
                                'misconduct' => 'Misconduct',
                                'safety_breach' => 'Safety Breach',
                                'harassment' => 'Harassment',
                                'fraud_or_theft' => 'Fraud / Theft',
                                'policy_breach' => 'Policy Breach',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('severity')
                            ->options([
                                'minor' => 'Minor',
                                'moderate' => 'Moderate',
                                'major' => 'Major',
                                'gross_misconduct' => 'Gross Misconduct',
                            ])
                            ->required()
                            ->default('minor')
                            ->native(false),
                    ]),
                    Forms\Components\Textarea::make('allegation')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('attachment_path')
                        ->disk('public')
                        ->directory('employees/disciplinary-cases')
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Due Process & Hearing')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DateTimePicker::make('show_cause_issued_at')->seconds(false),
                        Forms\Components\DateTimePicker::make('hearing_date')->seconds(false),
                    ]),
                    Forms\Components\Textarea::make('employee_response')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('hearing_notes')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('findings')->rows(3)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Decision')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('sanction')
                            ->options([
                                'none' => 'No Action',
                                'verbal_warning' => 'Verbal Warning',
                                'written_warning' => 'Written Warning',
                                'final_warning' => 'Final Written Warning',
                                'suspension' => 'Suspension',
                                'demotion' => 'Demotion',
                                'termination' => 'Termination',
                            ])
                            ->native(false),
                        Forms\Components\DatePicker::make('decision_date')->native(false),
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'investigating' => 'Investigating',
                                'awaiting_response' => 'Awaiting Employee Response',
                                'hearing_scheduled' => 'Hearing Scheduled',
                                'decided' => 'Decided',
                                'appealed' => 'Appealed',
                                'closed' => 'Closed',
                            ])
                            ->default('open')
                            ->required()
                            ->native(false),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('suspension_start_date')->native(false),
                        Forms\Components\DatePicker::make('suspension_end_date')->native(false),
                    ]),
                    Forms\Components\Textarea::make('appeal_notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('case_number')
            ->defaultSort('incident_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('case_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('incident_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->badge(),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'minor' => 'gray',
                        'moderate' => 'info',
                        'major' => 'warning',
                        'gross_misconduct' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('allegation')->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('sanction')
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline()->toString() : 'Pending')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manage disciplinary cases')
                        || auth()->user()?->can('edit employees'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['updated_by'] = auth()->id();
                        $data['reported_by'] ??= auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manage disciplinary cases')
                        || auth()->user()?->can('edit employees'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['updated_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->bulkActions([]);
    }
}
