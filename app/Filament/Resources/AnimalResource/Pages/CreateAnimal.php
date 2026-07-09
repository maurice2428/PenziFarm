<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Models\Breed;
use App\Services\AnimalTagGeneratorService;
use App\Services\BreedPurityService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAnimal extends CreateRecord
{
    protected static string $resource = AnimalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $breed = Breed::findOrFail($data['breed_id']);

        if (blank($data['date_of_birth'] ?? null)) {
            throw ValidationException::withMessages([
                'date_of_birth' =>
                    'Date of birth is required because it determines the Penzi animal tag year.',
            ]);
        }

        unset(
            $data['manual_tag_number'],
            $data['penzi_tag_preview'],
            $data['purity_preview'],
            $data['tag_number'],
            $data['tag_sequence']
        );

        $data['species'] = $breed->parent_category;
        $data['purity_breed_id'] = $breed->id;

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['is_archived'] = false;

        $this->validatePurityBreed($data, $breed);
        $this->validateLineage($data);

        /*
        |--------------------------------------------------------------------------
        | Breeding / Sale Controls
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Purchase Data
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Death / Culling Data
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Breed Purity Rules
        |--------------------------------------------------------------------------
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

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $breed = Breed::query()->findOrFail($data['breed_id']);

            $tagData = app(AnimalTagGeneratorService::class)
                ->generateForBreedAndBirthDate(
                    $breed,
                    $data['date_of_birth']
                );

            $data['tag_number'] = $tagData['tag_number'];
            $data['tag_sequence'] = $tagData['tag_sequence'];

            $animal = new Animal();

            $animal->forceFill($data);
            $animal->save();

            return $animal;
        }, 5);
    }

    protected function afterCreate(): void
    {
        app(BreedPurityService::class)->recalculate($this->record);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Animal created successfully.')
            ->body(
                'Issued Penzi tag: '
                . ($this->record?->tag_number ?? 'Pending')
            );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->url(AnimalResource::getUrl('index')),
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

        if (! $purityBreed) {
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

        if ($sireId && $damId && $sireId === $damId) {
            throw ValidationException::withMessages([
                'dam_id' => 'Sire and dam cannot be the same animal.',
            ]);
        }

        $animalDob = Carbon::parse($data['date_of_birth']);

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
            $data['species'],
            $animalDob
        );

        $this->validateParent(
            $parents->get($damId),
            'Female',
            'dam_id',
            $data['species'],
            $animalDob
        );
    }

    private function validateParent(
        ?Animal $parent,
        string $requiredSex,
        string $field,
        ?string $species,
        Carbon $animalDob
    ): void {
        if (! $parent) {
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
            $parent->date_of_birth
            && Carbon::parse($parent->date_of_birth)
                ->greaterThan($animalDob->copy()->subYear())
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'The selected parent must be at least one year older than the animal.',
            ]);
        }
    }
}
