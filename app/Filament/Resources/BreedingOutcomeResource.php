<?php

namespace App\Filament\Resources;

use App\Filament\Pages\ProgenyExplorer;
use App\Filament\Resources\BreedingOutcomeResource\Pages;
use App\Models\Animal;
use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BreedingOutcomeResource extends Resource
{
    protected static ?string $model = BreedingRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Breeding Management';

    protected static ?string $navigationLabel = 'Breeding Outcomes';

    protected static ?string $modelLabel = 'Breeding Outcome';

    protected static ?string $pluralModelLabel = 'Breeding Outcomes';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view breeding outcomes')
            || auth()->user()?->hasAnyRole([
                'Administrator',
                'Admin',
                'Manager',
                'Veterinary Officer',
            ])
            || false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit breeding outcomes')
            || auth()->user()?->hasAnyRole([
                'Administrator',
                'Admin',
                'Manager',
                'Veterinary Officer',
            ])
            || false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Breeding Pair')
                ->description('The sire and dam are taken from the original breeding record and cannot be changed during delivery confirmation.')
                ->icon('heroicon-o-link')
                ->columns(4)
                ->schema([
                    Forms\Components\Placeholder::make('female_display')
                        ->label('Dam / Mother')
                        ->content(fn (?BreedingRecord $record): HtmlString => new HtmlString(
                            '<strong>' . e($record?->female?->tag_number ?? '-') . '</strong><br>'
                            . e($record?->female?->breed?->breed_name ?? 'Unknown breed') . '<br>'
                            . '<span style="color:#6b7280">Location: '
                            . e($record?->female?->location?->name ?? 'Not assigned')
                            . '</span>'
                        )),

                    Forms\Components\Placeholder::make('male_display')
                        ->label('Sire / Father')
                        ->content(fn (?BreedingRecord $record): HtmlString => new HtmlString(
                            '<strong>' . e($record?->male?->tag_number ?? '-') . '</strong><br>'
                            . e($record?->male?->breed?->breed_name ?? 'Unknown breed')
                        )),

                    Forms\Components\DatePicker::make('mating_date')
                        ->label('Mating Date')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\DatePicker::make('expected_due_date')
                        ->label('Expected Due Date')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Forms\Components\Section::make('Minimum Gestation Protection')
                ->description('Delivery cannot be confirmed before the stored gestation period is complete.')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\Placeholder::make('gestation_guard')
                        ->label('Delivery Eligibility')
                        ->content(function (?BreedingRecord $record): HtmlString {
                            if (! $record) {
                                return new HtmlString('<span>Breeding record unavailable.</span>');
                            }

                            $minimum = $record->minimumDeliveryDate();

                            if (! $minimum) {
                                return new HtmlString(
                                    '<div style="padding:12px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b">'
                                    . '<strong>Delivery is blocked.</strong><br>Mating date or gestation days are missing.'
                                    . '</div>'
                                );
                            }

                            $remaining = $record->gestationDaysRemaining();
                            $eligible = today('Africa/Nairobi')->greaterThanOrEqualTo($minimum);
                            $color = $eligible ? '#15803d' : '#b45309';
                            $background = $eligible ? '#f0fdf4' : '#fffbeb';
                            $border = $eligible ? '#86efac' : '#fcd34d';
                            $headline = $eligible
                                ? 'Minimum gestation period completed'
                                : 'Delivery is not yet allowed';
                            $detail = $eligible
                                ? 'The earliest permitted delivery date was ' . $minimum->format('d M Y') . '.'
                                : 'Earliest permitted delivery date: ' . $minimum->format('d M Y')
                                    . '. Remaining: ' . number_format((int) $remaining) . ' day(s).';

                            return new HtmlString(
                                '<div style="padding:13px 15px;border:1px solid ' . $border
                                . ';border-left:6px solid ' . $color
                                . ';background:' . $background . ';color:' . $color . '">'
                                . '<strong>' . e($headline) . '</strong><br>'
                                . e($detail)
                                . '<br><span style="font-size:11px;color:#6b7280">Stored gestation: '
                                . number_format((int) $record->gestation_days) . ' days.</span>'
                                . '</div>'
                            );
                        }),
                ]),

            Forms\Components\Section::make('Pregnancy & Delivery Outcome')
                ->icon('heroicon-o-clipboard-document-check')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('pregnancy_status')
                        ->label('Pregnancy Status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed Pregnant',
                            'not_pregnant' => 'Not Pregnant',
                            'delivered' => 'Delivered',
                            'aborted' => 'Aborted',
                        ])
                        ->required()
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(function (
                            mixed $state,
                            Get $get,
                            Set $set,
                            ?BreedingRecord $record,
                        ): void {
                            if ($state !== 'delivered' || ! $record) {
                                return;
                            }

                            $deliveryDate = $get('delivery_date')
                                ?: today('Africa/Nairobi')->toDateString();

                            $message = static::deliveryValidationMessage(
                                $record,
                                $deliveryDate,
                            );

                            if (! $message) {
                                return;
                            }

                            /*
                             * Restore the last saved status so an early delivery
                             * is never left selected silently.
                             */
                            $set(
                                'pregnancy_status',
                                $record->pregnancy_status === 'delivered'
                                    ? 'confirmed'
                                    : ($record->pregnancy_status ?: 'confirmed'),
                            );

                            Notification::make()
                                ->danger()
                                ->title('Delivery cannot be confirmed')
                                ->body($message)
                                ->persistent()
                                ->send();
                        }),

                    Forms\Components\DatePicker::make('pregnancy_checked_at')
                        ->label('Pregnancy Checked At')
                        ->native(false)
                        ->maxDate(today()),

                    Forms\Components\DatePicker::make('delivery_date')
                        ->label('Delivery Date')
                        ->native(false)
                        ->live()
                        ->required(fn (Get $get): bool => $get('pregnancy_status') === 'delivered')
                        ->minDate(fn (?BreedingRecord $record) => $record?->minimumDeliveryDate())
                        ->maxDate(today('Africa/Nairobi'))
                        ->helperText(fn (?BreedingRecord $record): string => $record?->minimumDeliveryDate()
                            ? 'Earliest allowed: ' . $record->minimumDeliveryDate()?->format('d M Y')
                            : 'Delivery is blocked until mating date and gestation days are available.')
                        ->afterStateUpdated(function (
                            mixed $state,
                            Get $get,
                            ?BreedingRecord $record,
                        ): void {
                            if (
                                ! $record
                                || $get('pregnancy_status') !== 'delivered'
                                || blank($state)
                            ) {
                                return;
                            }

                            $message = static::deliveryValidationMessage(
                                $record,
                                $state,
                            );

                            if (! $message) {
                                return;
                            }

                            Notification::make()
                                ->danger()
                                ->title('Invalid delivery date')
                                ->body($message)
                                ->persistent()
                                ->send();
                        }),

                    Forms\Components\Select::make('birth_outcome')
                        ->label('Birth Outcome')
                        ->options([
                            'pending' => 'Pending',
                            'live_birth' => 'Live Birth',
                            'stillbirth' => 'Stillbirth',
                            'mixed' => 'Mixed Live and Stillborn',
                            'aborted' => 'Aborted',
                            'not_applicable' => 'Not Applicable',
                        ])
                        ->required()
                        ->live()
                        ->native(false),

                    Forms\Components\Select::make('birth_assistance')
                        ->label('Birth Assistance')
                        ->options([
                            'none' => 'No Assistance',
                            'minor' => 'Minor Assistance',
                            'major' => 'Major Assistance',
                            'surgical' => 'Surgical / Caesarean',
                        ])
                        ->native(false),

                    Forms\Components\TextInput::make('offspring_count')
                        ->label('Total Offspring')
                        ->numeric()
                        ->minValue(0)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Calculated automatically from live births plus stillborn offspring.'),

                    Forms\Components\TextInput::make('live_birth_count')
                        ->label('Live Births')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Get $get, Set $set, ?BreedingRecord $record): void {
                            $live = max(0, (int) $state);
                            $stillborn = max(0, (int) ($get('stillborn_count') ?? 0));
                            $set('offspring_count', $live + $stillborn);

                            $existing = static::existingOffspringCount($record);
                            $required = max(0, $live - $existing);
                            $currentRows = collect($get('new_offspring') ?? [])->filter(fn ($row) => is_array($row));

                            if ($currentRows->count() === $required) {
                                return;
                            }

                            $set('new_offspring', static::defaultOffspringRows($required, $record));
                        }),

                    Forms\Components\TextInput::make('stillborn_count')
                        ->label('Stillborn')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                            $set('offspring_count', max(0, (int) ($get('live_birth_count') ?? 0)) + max(0, (int) $state));
                        }),

                    Forms\Components\TextInput::make('neonatal_death_count')
                        ->label('Neonatal Deaths')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\TextInput::make('weaned_count')
                        ->label('Weaned')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\TextInput::make('retained_breeding_count')
                        ->label('Retained as Breeders')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\Textarea::make('delivery_notes')
                        ->label('Delivery Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Already Registered Offspring')
                ->description('These animal records were generated from this breeding delivery.')
                ->icon('heroicon-o-check-badge')
                ->visible(fn (?BreedingRecord $record): bool => filled($record?->getKey()))
                ->schema([
                    Forms\Components\Placeholder::make('existing_offspring_summary')
                        ->label('')
                        ->content(fn (?BreedingRecord $record): HtmlString => static::existingOffspringHtml($record)),
                ]),

            Forms\Components\Section::make('Register Live Offspring')
                ->description('The system generates tags using the existing Penzi tag service. Birth date, sire and dam are locked to the confirmed delivery record. Purity is recalculated automatically.')
                ->icon('heroicon-o-sparkles')
                ->visible(fn (Get $get, ?BreedingRecord $record): bool =>
                    $get('pregnancy_status') === 'delivered'
                    && (int) ($get('live_birth_count') ?? 0) > static::existingOffspringCount($record))
                ->schema([
                    Forms\Components\Repeater::make('new_offspring')
                        ->label('New Live Offspring')
                        ->dehydrated(false)
                        ->defaultItems(0)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => filled($state['sex'] ?? null)
                            ? ($state['sex'] . ' offspring')
                            : 'New offspring')
                        ->columns(12)
                        ->schema([
                            Forms\Components\Hidden::make('creation_token')
                                ->default(fn (): string => (string) Str::uuid()),

                            Forms\Components\Select::make('sex')
                                ->label('Sex')
                                ->options([
                                    'Male' => 'Male',
                                    'Female' => 'Female',
                                ])
                                ->required()
                                ->native(false)
                                ->columnSpan(2),

                            /*
                             * Offspring retain the dam's registered breed.
                             * The breed is displayed but cannot be changed, so
                             * the user never needs to select it again.
                             */
                            Forms\Components\Hidden::make('breed_id')
                                ->default(
                                    fn (?BreedingRecord $record): ?int =>
                                        $record?->female?->breed_id
                                )
                                ->afterStateHydrated(function (
                                    mixed $state,
                                    Set $set,
                                    ?BreedingRecord $record,
                                ): void {
                                    if (blank($state)) {
                                        $set(
                                            'breed_id',
                                            $record?->female?->breed_id,
                                        );
                                    }
                                }),

                            Forms\Components\Placeholder::make('breed_display')
                                ->label('Offspring Breed')
                                ->content(
                                    fn (?BreedingRecord $record): string =>
                                        $record?->female?->breed?->breed_name
                                        ?? 'Dam breed not available'
                                )
                                ->helperText(
                                    'Automatically inherited from the dam. '
                                    . 'This breed controls the Penzi tag prefix '
                                    . 'and breed-purity calculation.'
                                )
                                ->columnSpan(4),

                            Forms\Components\Placeholder::make('birth_date_preview')
                                ->label('Birth Date')
                                ->content(fn (?BreedingRecord $record): string => $record?->delivery_date?->format('d M Y')
                                    ?? 'Uses the delivery date selected above')
                                ->columnSpan(3),

                            Forms\Components\Select::make('current_location_id')
                                ->label('Location')
                                ->options(fn (): array => Location::query()
                                    ->active()
                                    ->defaultFirst()
                                    ->pluck('name', 'id')
                                    ->toArray())
                                ->default(fn (?BreedingRecord $record): ?int => $record?->female?->current_location_id)
                                ->searchable()
                                ->preload()
                                ->helperText('Defaults to the dam’s current location.')
                                ->columnSpan(3),

                            Forms\Components\Select::make('purpose')
                                ->label('Initial Purpose')
                                ->options([
                                    'Production' => 'Production',
                                    'Breeding' => 'Breeding',
                                    'Sale' => 'Sale',
                                    'Dairy' => 'Dairy',
                                ])
                                ->default('Production')
                                ->required()
                                ->native(false)
                                ->columnSpan(3),

                            Forms\Components\Textarea::make('notes')
                                ->label('Offspring Notes')
                                ->rows(2)
                                ->columnSpan(9),
                        ])
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Maternal Performance Assessment')
                ->description('Score each trait from 1 (poor) to 5 (excellent).')
                ->icon('heroicon-o-star')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('mothering_score')
                        ->label('Mothering Score')
                        ->numeric()->minValue(1)->maxValue(5)->step(0.25)->suffix('/5'),
                    Forms\Components\TextInput::make('milk_score')
                        ->label('Milk Score')
                        ->numeric()->minValue(1)->maxValue(5)->step(0.25)->suffix('/5'),
                    Forms\Components\TextInput::make('temperament_score')
                        ->label('Temperament Score')
                        ->numeric()->minValue(1)->maxValue(5)->step(0.25)->suffix('/5'),
                    Forms\Components\TextInput::make('offspring_vigour_score')
                        ->label('Offspring Vigour')
                        ->numeric()->minValue(1)->maxValue(5)->step(0.25)->suffix('/5'),
                    Forms\Components\Textarea::make('maternal_notes')
                        ->label('Mothering and Behaviour Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'female.breed',
                'male.breed',
                'batch',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('female.tag_number')->label('Dam')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('male.tag_number')->label('Sire')->searchable(),
                Tables\Columns\TextColumn::make('batch.batch_number')->label('Batch')->default('-'),
                Tables\Columns\TextColumn::make('mating_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('expected_due_date')->label('Minimum Due')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('pregnancy_status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'pending')->replace('_', ' ')->title()->toString())
                    ->color(fn (?string $state): string => match ($state) {
                        'confirmed', 'delivered' => 'success',
                        'aborted' => 'danger',
                        'not_pregnant' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('live_birth_count')->label('Live')->badge()->color('success'),
                Tables\Columns\TextColumn::make('stillborn_count')->label('Stillborn')->badge()->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('offspring_count')->label('Total')->badge()->color('info'),
                Tables\Columns\TextColumn::make('mothering_score')
                    ->label('Mothering')
                    ->formatStateUsing(fn ($state): string => $state === null ? '-' : number_format((float) $state, 2) . '/5')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 4 => 'success',
                        (float) $state >= 3 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pregnancy_status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'not_pregnant' => 'Not Pregnant',
                    'delivered' => 'Delivered',
                    'aborted' => 'Aborted',
                ]),
                Tables\Filters\SelectFilter::make('female_animal_id')
                    ->label('Dam')->relationship('female', 'tag_number')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('male_animal_id')
                    ->label('Sire')->relationship('male', 'tag_number')->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('damProgeny')
                    ->label('Dam Progeny')->icon('heroicon-o-share')
                    ->url(fn (BreedingRecord $record): string => ProgenyExplorer::getUrl(['animal' => $record->female_animal_id])),
                Tables\Actions\Action::make('sireProgeny')
                    ->label('Sire Progeny')->icon('heroicon-o-share')
                    ->url(fn (BreedingRecord $record): string => ProgenyExplorer::getUrl(['animal' => $record->male_animal_id])),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('mating_date', 'desc');
    }

    private static function deliveryValidationMessage(
        ?BreedingRecord $record,
        mixed $deliveryDate,
    ): ?string {
        if (! $record) {
            return 'The breeding record could not be loaded.';
        }

        try {
            $record->assertDeliveryDateMeetsGestation($deliveryDate);

            return null;
        } catch (ValidationException $exception) {
            return collect($exception->errors())
                ->flatten()
                ->filter()
                ->first()
                ?: 'The selected delivery date is not allowed.';
        }
    }

    private static function existingOffspringCount(?BreedingRecord $record): int
    {
        if (! $record?->getKey()) {
            return 0;
        }

        return Animal::query()
            ->where('source_reference_type', BreedingRecord::class)
            ->where('source_reference_id', $record->getKey())
            ->count();
    }

    /** @return array<int, array<string, mixed>> */
    private static function defaultOffspringRows(int $count, ?BreedingRecord $record): array
    {
        if ($count <= 0) {
            return [];
        }

        return collect(range(1, $count))
            ->map(fn (): array => [
                'creation_token' => (string) Str::uuid(),
                'sex' => null,
                'breed_id' => $record?->female?->breed_id,
                'current_location_id' => $record?->female?->current_location_id,
                'purpose' => 'Production',
                'notes' => null,
            ])
            ->values()
            ->all();
    }

    private static function existingOffspringHtml(?BreedingRecord $record): HtmlString
    {
        if (! $record?->getKey()) {
            return new HtmlString('<div>No offspring registered.</div>');
        }

        $offspring = Animal::query()
            ->with(['breed', 'location'])
            ->where('source_reference_type', BreedingRecord::class)
            ->where('source_reference_id', $record->getKey())
            ->orderBy('tag_number')
            ->get();

        if ($offspring->isEmpty()) {
            return new HtmlString(
                '<div style="border:1px dashed #cbd5e1;padding:14px;color:#64748b;background:#f8fafc">'
                . 'No live offspring have been registered from this delivery.'
                . '</div>'
            );
        }

        $rows = $offspring->map(function (Animal $animal): string {
            $purity = $animal->breed_purity_percent !== null
                ? number_format((float) $animal->breed_purity_percent, 2) . '%'
                : 'Pending';

            return '<tr>'
                . '<td style="padding:7px;border:1px solid #e5e7eb"><strong>' . e($animal->tag_number) . '</strong></td>'
                . '<td style="padding:7px;border:1px solid #e5e7eb">' . e($animal->sex) . '</td>'
                . '<td style="padding:7px;border:1px solid #e5e7eb">' . e($animal->breed?->breed_name ?? '-') . '</td>'
                . '<td style="padding:7px;border:1px solid #e5e7eb">' . e($purity) . '</td>'
                . '<td style="padding:7px;border:1px solid #e5e7eb">' . e($animal->location?->name ?? '-') . '</td>'
                . '</tr>';
        })->implode('');

        return new HtmlString(
            '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">'
            . '<thead><tr style="background:#14532d;color:white">'
            . '<th style="padding:8px;text-align:left">Tag</th>'
            . '<th style="padding:8px;text-align:left">Sex</th>'
            . '<th style="padding:8px;text-align:left">Breed</th>'
            . '<th style="padding:8px;text-align:left">Purity</th>'
            . '<th style="padding:8px;text-align:left">Location</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table></div>'
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreedingOutcomes::route('/'),
            'edit' => Pages\EditBreedingOutcome::route('/{record}/edit'),
        ];
    }
}
