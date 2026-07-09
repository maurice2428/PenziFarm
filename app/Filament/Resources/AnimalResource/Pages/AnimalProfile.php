<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AnimalProfile extends ViewRecord
{
    protected static string $resource = AnimalResource::class;

    protected static string $view = 'filament.resources.animal-resource.pages.animal-profile';

    /**
     * Resolve the record through Filament's native ViewRecord lifecycle.
     * This prevents the custom Page mount/findOrFail behaviour that produced
     * the misleading 404 after the route itself had already been registered.
     */
    protected function resolveRecord(int|string $key): Model
    {
        return Animal::query()
            ->with([
                'breed',
                'purityBreed',
                'location',
                'latestWeight',
                'sire.breed',
                'dam.breed',
                'sire.sire.breed',
                'sire.dam.breed',
                'dam.sire.breed',
                'dam.dam.breed',
                'healthAdministrations' => fn ($query) => $query
                    ->with('product')
                    ->orderByDesc('administered_at')
                    ->orderByDesc('id'),
                'clinicalCases' => fn ($query) => $query
                    ->orderByDesc('case_date')
                    ->orderByDesc('id'),
                'treatmentRecords' => fn ($query) => $query
                    ->with('clinicalCase')
                    ->orderByDesc('given_at')
                    ->orderByDesc('id'),
                'labRequests' => fn ($query) => $query
                    ->with(['clinicalCase', 'veterinaryClinic'])
                    ->orderByDesc('requested_at')
                    ->orderByDesc('id'),
            ])
            ->findOrFail($key);
    }

    public function getTitle(): string
    {
        $animal = $this->getRecord();

        return strtoupper($animal->breed?->breed_name ?? 'Animal')
            . ' Profile · '
            . $animal->tag_number;
    }

    public function getHeading(): string
    {
        return $this->getRecord()->tag_number . ' · Pedigree & Animal Profile';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToAnimals')
                ->label('Animals')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AnimalResource::getUrl('index')),

            Action::make('editAnimal')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->visible(
                    fn (): bool => auth()->user()?->can('edit animals') ?? false
                )
                ->url(
                    fn (): string => AnimalResource::getUrl('edit', [
                        'record' => $this->getRecord(),
                    ])
                ),

            Action::make('profilePdf')
                ->label('Profile PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(
                    fn () => redirect()->route(
                        'animals.profile.pdf',
                        ['animal' => $this->getRecord()->getKey()]
                    )
                ),
        ];
    }

    protected function getViewData(): array
    {
        $animal = $this->getRecord();

        return [
            'animal' => $animal,
            'profileUrl' => AnimalResource::getUrl('profile', [
                'record' => $animal,
            ]),
            'farmName' => setting('farm.name', 'Penzi Farm Limited'),
            'farmTagline' => setting(
                'farm.tagline',
                'Nurturing Quality, Inspiring Global Standards'
            ),
            'farmPhone' => setting('farm.phone', '+254 757 046 726'),
            'farmEmail' => setting('farm.email', 'jambo@penzifarm.com'),
            'farmCounty' => setting('farm.county', 'Molo - Nakuru County'),
            'primaryColor' => trim(setting('theme.primary', '#14532d')),
            'secondaryColor' => trim(setting('theme.secondary', '#166534')),
            'accentColor' => trim(setting('theme.accent', '#b7791f')),
            'logoUrl' => $this->logoUrl(),
        ];
    }

    private function logoUrl(): ?string
    {
        $path = trim((string) setting('branding.logo_light', ''));

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        $path = preg_replace('#^/?storage/#', '', ltrim($path, '/'));

        return asset('storage/' . $path);
    }
}
