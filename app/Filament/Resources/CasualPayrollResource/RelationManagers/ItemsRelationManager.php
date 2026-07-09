<?php

namespace App\Filament\Resources\CasualPayrollResource\RelationManagers;

use App\Models\HR\CasualPayrollItem;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\CasualPayrollResource\Pages\EditCasualPayroll;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Casual Workers';

    protected static ?string $modelLabel = 'Casual Worker';

    protected static ?string $pluralModelLabel = 'Casual Workers';
    private function refreshOwnerPayrollTotals(): void
{
    $this->ownerRecord->recalculateTotals();
    $this->ownerRecord->refresh();

    $this->dispatch('casual-payroll-totals-updated');
}

    public function form(Form $form): Form
    {
        $startDate = $this->ownerRecord?->week_start
            ? Carbon::parse($this->ownerRecord->week_start)
            : now();

        $dayLabels = [];

        for ($i = 0; $i < 7; $i++) {
            $dayLabels[] = $startDate->copy()->addDays($i);
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Casual Worker Details')
                    ->description('Enter the casual worker details and daily rate.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('casual_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('signature', self::makeSignatureFromName($state));
                            }),

                        Forms\Components\TextInput::make('id_number')
                            ->label('ID Number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('designation')
                            ->label('Designation')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('work_site')
                            ->label('Work Site')
                            ->default(fn () => $this->ownerRecord?->work_site)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('daily_rate')
                            ->label('Daily Rate')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),
                    ]),

                Forms\Components\Section::make('Daily Attendance')
                    ->description('Select P for each day the casual worker was present. The system will calculate days and total pay.')
                    ->columns(7)
                    ->schema([
                        Forms\Components\Select::make('day_1_present')
                            ->label($dayLabels[0]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->saturday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),

                        Forms\Components\Select::make('day_2_present')
                            ->label($dayLabels[1]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->sunday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),

                        Forms\Components\Select::make('day_3_present')
                            ->label($dayLabels[2]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->monday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),

                        Forms\Components\Select::make('day_4_present')
                            ->label($dayLabels[3]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->tuesday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),

                        Forms\Components\Select::make('day_5_present')
                            ->label($dayLabels[4]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->wednesday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),

                        Forms\Components\Select::make('day_6_present')
                            ->label($dayLabels[5]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->thursday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),

                        Forms\Components\Select::make('day_7_present')
                            ->label($dayLabels[6]->format('D d/m'))
                            ->options(['P' => 'P'])
                            ->placeholder('-')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?CasualPayrollItem $record) {
                                $component->state(((float) ($record?->friday_amount ?? 0)) > 0 ? 'P' : null);
                            })
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculateWorkerTotals($get, $set)),
                    ]),

                Forms\Components\Section::make('Totals & Remarks')
                    ->description('Totals are calculated automatically from daily rate and present days.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('days_worked')
                            ->label('Days Worked')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('total_pay')
                            ->label('Total Pay')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->readOnly()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('signature')
                            ->label('Signature')
                            ->maxLength(255)
                            ->readOnly()
                            ->dehydrated(true),

                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('casual_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('id_number')
                    ->label('ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('designation')
                    ->label('Designation')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('work_site')
                    ->label('Work Site')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('days_worked')
                    ->label('Days')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),

                Tables\Columns\TextColumn::make('total_pay')
                    ->label('Total Pay')
                    ->money('KES')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('signature')
                    ->label('Signature')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Casual Worker')
                    ->mutateFormDataUsing(fn (array $data): array => self::mutateAttendanceData($data))
                    //->after(fn () => $this->ownerRecord->recalculateTotals())
                    ->after(fn () => $this->refreshOwnerPayrollTotals())
                    ->visible(fn () => auth()->user()?->can('edit casual payroll') ?? false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => self::mutateAttendanceData($data))
                   // ->after(fn () => $this->ownerRecord->recalculateTotals())
                   ->after(fn () => $this->refreshOwnerPayrollTotals())
                    ->visible(fn () => auth()->user()?->can('edit casual payroll') ?? false),

                Tables\Actions\DeleteAction::make()
                   // ->after(fn () => $this->ownerRecord->recalculateTotals())
                   ->after(fn () => $this->refreshOwnerPayrollTotals())
                    ->visible(fn () => auth()->user()?->can('delete casual payroll') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    //->after(fn () => $this->ownerRecord->recalculateTotals())
                    ->after(fn () => $this->refreshOwnerPayrollTotals())
                    ->visible(fn () => auth()->user()?->can('delete casual payroll') ?? false),
            ]);
    }

    private static function recalculateWorkerTotals(Forms\Get $get, Forms\Set $set): void
    {
        $dailyRate = (float) ($get('daily_rate') ?? 0);

        $daysWorked = collect([
            $get('day_1_present'),
            $get('day_2_present'),
            $get('day_3_present'),
            $get('day_4_present'),
            $get('day_5_present'),
            $get('day_6_present'),
            $get('day_7_present'),
        ])
            ->filter(fn ($value) => $value === 'P')
            ->count();

        $set('days_worked', $daysWorked);
        $set('total_pay', $dailyRate * $daysWorked);
    }

    private static function makeSignatureFromName(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $name);

        if (! $parts || count($parts) === 0) {
            return null;
        }

        $firstName = $parts[0] ?? null;
        $lastName = count($parts) > 1 ? $parts[count($parts) - 1] : null;

        return trim(collect([$firstName, $lastName])
            ->filter()
            ->map(fn ($part) => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'))
            ->implode(' '));
    }

    private static function mutateAttendanceData(array $data): array
    {
        $dailyRate = (float) ($data['daily_rate'] ?? 0);

        $day1 = ($data['day_1_present'] ?? null) === 'P';
        $day2 = ($data['day_2_present'] ?? null) === 'P';
        $day3 = ($data['day_3_present'] ?? null) === 'P';
        $day4 = ($data['day_4_present'] ?? null) === 'P';
        $day5 = ($data['day_5_present'] ?? null) === 'P';
        $day6 = ($data['day_6_present'] ?? null) === 'P';
        $day7 = ($data['day_7_present'] ?? null) === 'P';

        $daysWorked = collect([$day1, $day2, $day3, $day4, $day5, $day6, $day7])
            ->filter()
            ->count();

        $data['saturday_amount'] = $day1 ? $dailyRate : 0;
        $data['sunday_amount'] = $day2 ? $dailyRate : 0;
        $data['monday_amount'] = $day3 ? $dailyRate : 0;
        $data['tuesday_amount'] = $day4 ? $dailyRate : 0;
        $data['wednesday_amount'] = $day5 ? $dailyRate : 0;
        $data['thursday_amount'] = $day6 ? $dailyRate : 0;
        $data['friday_amount'] = $day7 ? $dailyRate : 0;

        $data['days_worked'] = $daysWorked;
        $data['total_pay'] = $dailyRate * $daysWorked;
        $data['signature'] = self::makeSignatureFromName($data['casual_name'] ?? null);

        unset(
            $data['day_1_present'],
            $data['day_2_present'],
            $data['day_3_present'],
            $data['day_4_present'],
            $data['day_5_present'],
            $data['day_6_present'],
            $data['day_7_present'],
        );

        return $data;
    }
}
