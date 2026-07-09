<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Models\AnimalTagCorrection;
use App\Models\Breed;
use App\Services\AnimalTagGeneratorService;
use App\Services\BreedPurityService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditAnimal extends EditRecord
{
    protected static string $resource = AnimalResource::class;

    protected ?string $oldStatus = null;

    protected bool $redirectToInvoice = false;

    protected function beforeSave(): void
    {
        $this->oldStatus = $this->record->getOriginal('status');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
         * |--------------------------------------------------------------------------
         * | Immutable Animal Identity
         * |--------------------------------------------------------------------------
         * |
         * | Breed, date of birth, tag number and tag sequence are protected during
         * | normal editing. They can only be changed through the Administrator-only
         * | Correct Animal Identity action.
         * |
         */
        $breed = Breed::query()->findOrFail($this->record->breed_id);

        $validationData = array_merge($data, [
            'breed_id' => $breed->id,
            'species' => $breed->parent_category,
            'purity_breed_id' => $this->record->purity_breed_id ?: $breed->id,
            'date_of_birth' => $this->record->date_of_birth,
            'date_of_birth_is_estimated' => $this->record->date_of_birth_is_estimated,
        ]);

        $this->validatePurityBreed($validationData, $breed);
        $this->validateLineage($validationData);

        unset(
            $data['manual_tag_number'],
            $data['tag_number'],
            $data['tag_sequence'],
            $data['breed_id'],
            $data['species'],
            $data['date_of_birth'],
            $data['date_of_birth_is_estimated'],
            $data['purity_breed_id'],
            $data['penzi_tag_preview'],
            $data['purity_preview'],
        );

        $data['updated_by'] = auth()->id();

        /*
         * |--------------------------------------------------------------------------
         * | Breeding / Sale Controls
         * |--------------------------------------------------------------------------
         */
        if (($data['purpose'] ?? null) === 'Breeding') {
            $data['is_breeder'] = true;
            $data['sale_ready'] = false;
        }

        if (($data['is_breeder'] ?? false) === true) {
            $data['sale_ready'] = false;
        }

        if (($data['sale_ready'] ?? false) === true) {
            $data['is_breeder'] = false;
        }

        /*
         * |--------------------------------------------------------------------------
         * | Purchase Data
         * |--------------------------------------------------------------------------
         */
        if (($data['source'] ?? null) !== 'Purchased') {
            $data['bought_on'] = null;
            $data['bought_from'] = null;
            $data['seller_phone'] = null;
            $data['seller_email'] = null;
            $data['seller_address'] = null;
            $data['purchase_price'] = null;
            $data['purchase_notes'] = null;
        }

        /*
         * |--------------------------------------------------------------------------
         * | Death / Culling Data
         * |--------------------------------------------------------------------------
         */
        if (($data['status'] ?? null) !== 'Dead') {
            $data['date_died'] = null;
            $data['cause_of_death'] = null;
            $data['death_comments'] = null;
        }

        if (($data['status'] ?? null) !== 'Culled') {
            $data['date_culled'] = null;
            $data['culling_reason'] = null;
            $data['culling_comments'] = null;
        }

        /*
         * |--------------------------------------------------------------------------
         * | Breed Purity Rules
         * |--------------------------------------------------------------------------
         */
        if (($data['is_foundation_animal'] ?? false) === true) {
            $data['purity_status'] = 'foundation';
            $data['purity_override_percent'] = null;
            $data['purity_verified_at'] ??= now()->toDateString();
        } elseif (in_array(
            $data['purity_status'] ?? 'pending',
            ['dna_verified', 'manual_verified'],
            true
        )) {
            if (blank($data['purity_override_percent'] ?? null)) {
                throw ValidationException::withMessages([
                    'purity_override_percent' =>
                        'Enter the verified breed purity percentage.',
                ]);
            }
        } else {
            $data['purity_status'] = 'pending';
            $data['purity_override_percent'] = null;
            $data['purity_verified_at'] = null;
        }

        /*
         * |--------------------------------------------------------------------------
         * | Sold Animal Flow
         * |--------------------------------------------------------------------------
         * |
         * | Keep the animal Active temporarily so it can be selected on the sales
         * | invoice. The invoice workflow changes the final status to Sold.
         * |
         */
        if (($data['status'] ?? null) === 'Sold') {
            $this->redirectToInvoice = true;

            $data['status'] = $this->oldStatus ?: 'Active';
            $data['sale_ready'] = true;
            $data['is_breeder'] = false;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(BreedPurityService::class)->recalculate($this->record);

        if ($this->redirectToInvoice) {
            session()->flash('sold_animal_notice', [
                'animal_id' => $this->record->id,
                'animal_tag' => $this->record->tag_number,
                'message' => 'Before proceeding, copy the Tag Number below.',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        if ($this->redirectToInvoice) {
            return url('/admin/sales/sales-invoices/create?' . http_build_query([
                'sold_animal_id' => $this->record->id,
                'sold_animal_tag' => $this->record->tag_number,
                'sold_notice' => 1,
            ]));
        }

        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->redirectToInvoice) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title('Animal updated successfully.')
            ->body('Breed purity and linked descendant records were recalculated.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('correctAnimalIdentity')
                ->label('Correct Animal Identity')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->visible(
                    fn(): bool =>
                        auth()->user()?->hasRole('Administrator') ?? false
                )
                ->modalHeading('Correct Animal Identity')
                ->modalDescription(
                    'Use only when the breed, date of birth, or issued tag is incorrect. '
                    . 'The old tag is permanently retired and every correction is recorded.'
                )
                ->modalSubmitActionLabel('Apply Correction')
                ->form([
                    Forms\Components\Placeholder::make('current_tag')
                        ->label('Current Permanent Tag')
                        ->content(fn(): string => $this->record->tag_number),
                    Forms\Components\Select::make('breed_id')
                        ->label('Correct Breed')
                        ->options(
                            Breed::query()
                                ->orderBy('parent_category')
                                ->orderBy('breed_name')
                                ->pluck('breed_name', 'id')
                                ->all()
                        )
                        ->default(fn(): ?int => $this->record->breed_id)
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->label('Correct Date of Birth')
                        ->default(fn() => $this->record->date_of_birth)
                        ->maxDate(today())
                        ->required(),
                    Forms\Components\Toggle::make('date_of_birth_is_estimated')
                        ->label('Estimated Date of Birth')
                        ->default(
                            fn(): bool =>
                                (bool) $this->record->date_of_birth_is_estimated
                        ),
                    Forms\Components\Select::make('correction_mode')
                        ->label('Tag Correction Method')
                        ->options([
                            'keep_tag' =>
                                'Keep existing tag — breed and birth year must remain unchanged',
                            'issue_next_tag' =>
                                'Retire existing tag and issue the next sequence',
                        ])
                        ->default('keep_tag')
                        ->helperText(
                            'Choose “issue next tag” for an erroneous physical or recorded tag. '
                            . 'Issued sequence numbers are never reused.'
                        )
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->label('Correction Reason')
                        ->placeholder(
                            'Explain what was incorrect and how the correction was verified.'
                        )
                        ->rows(4)
                        ->required()
                        ->minLength(10),
                ])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data): void {
                        $animal = Animal::query()
                            ->whereKey($this->record->getKey())
                            ->lockForUpdate()
                            ->firstOrFail();

                        $newBreed = Breed::query()
                            ->findOrFail($data['breed_id']);

                        $oldBirthDate = $animal->date_of_birth
                            ? Carbon::parse($animal->date_of_birth)
                            : null;

                        $newBirthDate = Carbon::parse($data['date_of_birth']);

                        $breedChanged =
                            (int) $animal->breed_id !== (int) $newBreed->id;

                        $birthYearChanged =
                            !$oldBirthDate ||
                            $oldBirthDate->year !== $newBirthDate->year;

                        if (
                            $data['correction_mode'] === 'keep_tag' &&
                            ($breedChanged || $birthYearChanged)
                        ) {
                            throw ValidationException::withMessages([
                                'correction_mode' =>
                                    'The existing tag cannot be retained when the breed '
                                    . 'or birth year changes. Select “Retire existing tag '
                                    . 'and issue the next sequence”.',
                            ]);
                        }

                        $lineageData = [
                            'sire_id' => $animal->sire_id,
                            'dam_id' => $animal->dam_id,
                            'species' => $newBreed->parent_category,
                            'date_of_birth' => $newBirthDate->toDateString(),
                        ];

                        $this->validateLineage($lineageData);

                        $oldTag = $animal->tag_number;
                        $oldBreedId = $animal->breed_id;
                        $oldDateOfBirth = $animal->date_of_birth;

                        $newTag = $oldTag;
                        $newSequence = $animal->tag_sequence;

                        if ($data['correction_mode'] === 'issue_next_tag') {
                            $tagData = app(
                                AnimalTagGeneratorService::class
                            )->generateForBreedAndBirthDate(
                                $newBreed,
                                $newBirthDate
                            );

                            $newTag = $tagData['tag_number'];
                            $newSequence = $tagData['tag_sequence'];
                        }

                        AnimalTagCorrection::query()->create([
                            'animal_id' => $animal->id,
                            'old_tag_number' => $oldTag,
                            'new_tag_number' => $newTag,
                            'old_breed_id' => $oldBreedId,
                            'new_breed_id' => $newBreed->id,
                            'old_date_of_birth' => $oldDateOfBirth,
                            'new_date_of_birth' => $newBirthDate->toDateString(),
                            'correction_type' => $data['correction_mode'],
                            'reason' => $data['reason'],
                            'corrected_by' => auth()->id(),
                        ]);

                        $animal->forceFill([
                            'tag_number' => $newTag,
                            'tag_sequence' => $newSequence,
                            'breed_id' => $newBreed->id,
                            'purity_breed_id' => $newBreed->id,
                            'species' => $newBreed->parent_category,
                            'date_of_birth' => $newBirthDate->toDateString(),
                            'date_of_birth_is_estimated' =>
                                (bool) ($data['date_of_birth_is_estimated'] ?? false),
                            'updated_by' => auth()->id(),
                        ])->save();

                        app(BreedPurityService::class)->recalculate($animal);
                    }, 5);

                    $this->record->refresh();

                    $this->refreshFormData([
                        'tag_number',
                        'tag_sequence',
                        'breed_id',
                        'purity_breed_id',
                        'species',
                        'date_of_birth',
                        'date_of_birth_is_estimated',
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Animal identity corrected')
                        ->body(
                            'Current permanent tag: '
                            . $this->record->tag_number
                        )
                        ->persistent()
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->visible(
                    fn(): bool =>
                        auth()->user()?->hasRole('Administrator') ?? false
                ),
        ];
    }

    private function validatePurityBreed(array $data, Breed $animalBreed): void
    {
        $purityBreedId = $data['purity_breed_id'] ?? null;

        if (blank($purityBreedId)) {
            throw ValidationException::withMessages([
                'purity_breed_id' =>
                    'Select the foundation breed used for purity calculation.',
            ]);
        }

        $purityBreed = Breed::find($purityBreedId);

        if (!$purityBreed) {
            throw ValidationException::withMessages([
                'purity_breed_id' => 'The selected purity breed is invalid.',
            ]);
        }

        if ($purityBreed->parent_category !== $animalBreed->parent_category) {
            throw ValidationException::withMessages([
                'purity_breed_id' =>
                    'The purity breed must belong to the same animal species.',
            ]);
        }
    }

    private function validateLineage(array $data): void
    {
        $sireId = filled($data['sire_id'] ?? null)
            ? (int) $data['sire_id']
            : null;

        $damId = filled($data['dam_id'] ?? null)
            ? (int) $data['dam_id']
            : null;

        if ($sireId && $sireId === $this->record->id) {
            throw ValidationException::withMessages([
                'sire_id' => 'An animal cannot be its own sire.',
            ]);
        }

        if ($damId && $damId === $this->record->id) {
            throw ValidationException::withMessages([
                'dam_id' => 'An animal cannot be its own dam.',
            ]);
        }

        if ($sireId && $damId && $sireId === $damId) {
            throw ValidationException::withMessages([
                'dam_id' => 'Sire and dam cannot be the same animal.',
            ]);
        }

        $animalDob = filled($data['date_of_birth'] ?? null)
            ? Carbon::parse($data['date_of_birth'])
            : null;

        $parents = Animal::query()
            ->whereIn(
                'id',
                collect([$sireId, $damId])->filter()->all()
            )
            ->get()
            ->keyBy('id');

        $this->validateParent(
            $parents->get($sireId),
            'Male',
            'sire_id',
            $data['species'] ?? null,
            $animalDob
        );

        $this->validateParent(
            $parents->get($damId),
            'Female',
            'dam_id',
            $data['species'] ?? null,
            $animalDob
        );
    }

    private function validateParent(
        ?Animal $parent,
        string $requiredSex,
        string $field,
        ?string $species,
        ?Carbon $animalDob
    ): void {
        if (!$parent) {
            return;
        }

        if ($parent->sex !== $requiredSex) {
            throw ValidationException::withMessages([
                $field => "The selected parent must be {$requiredSex}.",
            ]);
        }

        if ($species && $parent->species !== $species) {
            throw ValidationException::withMessages([
                $field => 'The selected parent must belong to the same species.',
            ]);
        }

        if (
            $animalDob &&
            $parent->date_of_birth &&
            Carbon::parse($parent->date_of_birth)
                ->greaterThan($animalDob->copy()->subYear())
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'The selected parent must be at least one year older than the animal.',
            ]);
        }
    }
}
