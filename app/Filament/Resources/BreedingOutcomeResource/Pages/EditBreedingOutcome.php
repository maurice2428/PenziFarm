<?php

namespace App\Filament\Resources\BreedingOutcomeResource\Pages;

use App\Filament\Resources\BreedingOutcomeResource;
use App\Models\Animal;
use App\Models\BreedingRecord;
use App\Services\BreedingDeliveryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditBreedingOutcome extends EditRecord
{
    protected static string $resource = BreedingOutcomeResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $newOffspringRows = [];

    /** @var array<int, string> */
    protected array $issuedOffspringTags = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing([
            'female.breed',
            'female.location',
            'male.breed',
            'male.location',
        ]);

        $existing = $this->existingOffspringCount();

        $required = max(
            0,
            (int) ($data['live_birth_count'] ?? 0) - $existing,
        );

        $data['new_offspring'] = $this->defaultOffspringRows($required);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawState = $this->form->getRawState();

        $this->newOffspringRows = collect(
            $rawState['new_offspring'] ?? []
        )
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(function (array $row): array {
                /*
                 * The breed is always inherited from the dam. Reapply it here
                 * so a blank or manipulated browser value cannot remove it.
                 */
                $row['breed_id'] = $this->record->female?->breed_id;
                $row['current_location_id'] = filled(
                    $row['current_location_id'] ?? null
                )
                    ? $row['current_location_id']
                    : $this->record->female?->current_location_id;

                return $row;
            })
            ->values()
            ->all();

        unset(
            $data['new_offspring'],
            $data['female_display'],
            $data['male_display'],
            $data['gestation_guard'],
            $data['existing_offspring_summary'],
            $data['birth_date_preview'],
            $data['breed_display'],
        );

        $pregnancyStatus = (string) (
            $data['pregnancy_status'] ?? 'pending'
        );

        $birthOutcome = (string) (
            $data['birth_outcome'] ?? 'pending'
        );

        $liveBirths = max(
            0,
            (int) ($data['live_birth_count'] ?? 0),
        );

        $stillborn = max(
            0,
            (int) ($data['stillborn_count'] ?? 0),
        );

        $neonatalDeaths = max(
            0,
            (int) ($data['neonatal_death_count'] ?? 0),
        );

        $weaned = max(
            0,
            (int) ($data['weaned_count'] ?? 0),
        );

        $retained = max(
            0,
            (int) ($data['retained_breeding_count'] ?? 0),
        );

        $existingOffspring = $this->existingOffspringCount();

        if ($pregnancyStatus === 'delivered') {
            try {
                $this->record->assertDeliveryDateMeetsGestation(
                    $data['delivery_date'] ?? null
                );
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())
                    ->flatten()
                    ->filter()
                    ->first()
                    ?: 'The selected delivery date is not allowed.';

                $this->failWithVisibleError(
                    field: 'delivery_date',
                    message: $message,
                    title: 'Delivery cannot be confirmed',
                );
            }

            if (! in_array(
                $birthOutcome,
                ['live_birth', 'stillbirth', 'mixed'],
                true
            )) {
                $this->failWithVisibleError(
                    field: 'birth_outcome',
                    message:
                        'Select Live Birth, Stillbirth, or Mixed '
                        . 'when confirming delivery.',
                );
            }

            if (
                $birthOutcome === 'live_birth'
                && $liveBirths < 1
            ) {
                $this->failWithVisibleError(
                    field: 'live_birth_count',
                    message: 'Enter at least one live birth.',
                );
            }

            if (
                $birthOutcome === 'stillbirth'
                && $stillborn < 1
            ) {
                $this->failWithVisibleError(
                    field: 'stillborn_count',
                    message: 'Enter at least one stillborn offspring.',
                );
            }

            if (
                $birthOutcome === 'mixed'
                && ($liveBirths < 1 || $stillborn < 1)
            ) {
                $this->failWithVisibleError(
                    field: 'birth_outcome',
                    message:
                        'A mixed delivery requires at least one live birth '
                        . 'and at least one stillborn offspring.',
                );
            }

            if ($liveBirths < $existingOffspring) {
                $this->failWithVisibleError(
                    field: 'live_birth_count',
                    message:
                        "{$existingOffspring} offspring animal record(s) "
                        . 'already exist. Live births cannot be reduced '
                        . 'below this number.',
                );
            }

            $requiredNew = $liveBirths - $existingOffspring;

            if (count($this->newOffspringRows) !== $requiredNew) {
                $this->failWithVisibleError(
                    field: 'new_offspring',
                    message:
                        "Add exactly {$requiredNew} new offspring row(s). "
                        . "{$existingOffspring} offspring are already "
                        . 'registered.',
                );
            }

            foreach ($this->newOffspringRows as $index => $row) {
                $number = $index + 1;

                if (! in_array(
                    $row['sex'] ?? null,
                    ['Male', 'Female'],
                    true
                )) {
                    $this->failWithVisibleError(
                        field: 'new_offspring',
                        message:
                            "Select the sex for offspring number {$number}.",
                    );
                }

                if (blank($this->record->female?->breed_id)) {
                    $this->failWithVisibleError(
                        field: 'new_offspring',
                        message:
                            'The dam has no registered breed. Correct the '
                            . 'dam record before confirming delivery.',
                    );
                }
            }

            $data['offspring_count'] = $liveBirths + $stillborn;

            if (
                Schema::hasColumn(
                    'breeding_records',
                    'evaluation_completed_at'
                )
            ) {
                $data['evaluation_completed_at'] = now();
            }
        } else {
            if ($existingOffspring > 0) {
                $this->failWithVisibleError(
                    field: 'pregnancy_status',
                    message:
                        'This record already has generated offspring. '
                        . 'Its pregnancy status must remain Delivered.',
                );
            }

            if ($this->newOffspringRows !== []) {
                $this->failWithVisibleError(
                    field: 'new_offspring',
                    message:
                        'Remove the offspring rows or change the '
                        . 'pregnancy status to Delivered.',
                );
            }
        }

        if ($neonatalDeaths > $liveBirths) {
            $this->failWithVisibleError(
                field: 'neonatal_death_count',
                message: 'Neonatal deaths cannot exceed live births.',
            );
        }

        if ($weaned > $liveBirths) {
            $this->failWithVisibleError(
                field: 'weaned_count',
                message: 'The weaned count cannot exceed live births.',
            );
        }

        if ($retained > $weaned) {
            $this->failWithVisibleError(
                field: 'retained_breeding_count',
                message:
                    'Animals retained for breeding cannot exceed '
                    . 'the number weaned.',
            );
        }

        if (
            Schema::hasColumn(
                'breeding_records',
                'updated_by'
            )
        ) {
            $data['updated_by'] = auth()->id();
        }

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        try {
            return DB::transaction(
                function () use ($record, $data): Model {
                    /** @var BreedingRecord $record */
                    $record->fill($data);
                    $record->save();

                    $createdAnimals = app(
                        BreedingDeliveryService::class
                    )->registerLiveOffspring(
                        breedingRecord: $record,
                        offspringRows: $this->newOffspringRows,
                    );

                    $this->issuedOffspringTags = $createdAnimals
                        ->pluck('tag_number')
                        ->filter()
                        ->values()
                        ->all();

                    if ($record->breeding_batch_id) {
                        $batch = $record->batch;

                        if ($batch) {
                            $hasOpenRecords = $batch->records()
                                ->whereNotIn(
                                    'pregnancy_status',
                                    [
                                        'delivered',
                                        'aborted',
                                        'not_pregnant',
                                    ]
                                )
                                ->exists();

                            if (! $hasOpenRecords) {
                                $batch->update([
                                    'status' => 'delivered',
                                ]);
                            }
                        }
                    }

                    return $record->refresh();
                },
                5
            );
        } catch (ValidationException $exception) {
            throw $this->visibleValidationException($exception);
        }
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        $this->data['new_offspring'] = [];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(
                fn (): string =>
                    ($this->data['pregnancy_status'] ?? null)
                        === 'delivered'
                        ? 'Confirm Delivery & Register Offspring'
                        : 'Save Breeding Outcome'
            )
            ->icon(
                fn (): string =>
                    ($this->data['pregnancy_status'] ?? null)
                        === 'delivered'
                        ? 'heroicon-o-check-badge'
                        : 'heroicon-o-check'
            )
            ->color(
                fn (): string =>
                    ($this->data['pregnancy_status'] ?? null)
                        === 'delivered'
                        ? 'success'
                        : 'primary'
            )
            ->requiresConfirmation(
                fn (): bool =>
                    ($this->data['pregnancy_status'] ?? null)
                        === 'delivered'
            )
            ->modalHeading('Confirm delivery and register offspring?')
            ->modalDescription(
                'The system will validate the minimum gestation period, '
                . 'save the delivery, generate permanent Penzi animal tags, '
                . 'inherit the dam breed, assign the recorded sire and dam, '
                . 'copy the dam location, and calculate breed purity.'
            )
            ->modalSubmitActionLabel(
                'Confirm Delivery & Generate Tags'
            );
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->issuedOffspringTags !== []) {
            return Notification::make()
                ->success()
                ->title(
                    'Delivery confirmed and offspring registered'
                )
                ->body(
                    'Issued Penzi tags: '
                    . implode(', ', $this->issuedOffspringTags)
                    . '. Breed was inherited from the dam. Birth date, '
                    . 'sire, dam, location and breed purity were assigned '
                    . 'automatically.'
                )
                ->persistent();
        }

        return Notification::make()
            ->success()
            ->title('Breeding outcome updated')
            ->body(
                'The pregnancy and delivery assessment '
                . 'was saved successfully.'
            );
    }

    private function existingOffspringCount(): int
    {
        return Animal::query()
            ->where(
                'source_reference_type',
                BreedingRecord::class
            )
            ->where(
                'source_reference_id',
                $this->record->getKey()
            )
            ->count();
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultOffspringRows(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        return collect(range(1, $count))
            ->map(fn (): array => [
                'creation_token' => (string) Str::uuid(),
                'sex' => null,
                'breed_id' => $this->record->female?->breed_id,
                'current_location_id' =>
                    $this->record->female?->current_location_id,
                'purpose' => 'Production',
                'notes' => null,
            ])
            ->values()
            ->all();
    }

    private function failWithVisibleError(
        string $field,
        string $message,
        string $title = 'Please correct the delivery details',
    ): never {
        Notification::make()
            ->danger()
            ->title($title)
            ->body($message)
            ->persistent()
            ->send();

        throw ValidationException::withMessages([
            'data.' . $field => $message,
        ]);
    }

    private function visibleValidationException(
        ValidationException $exception
    ): ValidationException {
        $mapped = [];

        foreach ($exception->errors() as $field => $messages) {
            /*
             * Numeric repeater indexes do not match Filament's UUID state
             * keys, so attach those errors to the repeater as a whole.
             */
            if (str_starts_with($field, 'new_offspring.')) {
                $field = 'new_offspring';
            }

            $mappedField = str_starts_with($field, 'data.')
                ? $field
                : 'data.' . $field;

            $mapped[$mappedField] = $messages;
        }

        $message = collect($mapped)
            ->flatten()
            ->filter()
            ->first()
            ?: 'The delivery could not be confirmed.';

        Notification::make()
            ->danger()
            ->title('Delivery could not be confirmed')
            ->body($message)
            ->persistent()
            ->send();

        return ValidationException::withMessages($mapped);
    }
}
