<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;

class AnimalProfile extends Page
{
    protected static string $resource = AnimalResource::class;

    protected static string $view = 'filament.resources.animal-resource.pages.animal-profile';

    public Animal $record;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()?->can('view animals') ?? false, 403);

        $animal = Animal::query()->findOrFail($record);

        $this->record = $this->loadProfile($animal);
    }

    public function getTitle(): string
    {
        return ($this->record->breed?->breed_name ?? 'Animal')
            . ' Profile - '
            . $this->record->tag_number;
    }

    public function getHeading(): string
    {
        return $this->record->tag_number . ' Animal Profile';
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
                ->label('Edit Animal')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->visible(
                    fn (): bool =>
                        auth()->user()?->can('edit animals') ?? false
                )
                ->url(
                    AnimalResource::getUrl('edit', [
                        'record' => $this->record,
                    ])
                ),

            Action::make('generateProfilePdf')
                ->label('Profile PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(
                    fn () => redirect()->route(
                        'animals.profile.pdf',
                        ['animal' => $this->record->getKey()]
                    )
                ),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'animal' => $this->record,
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
            'accentColor' => trim(setting('theme.accent', '#d97706')),
            'dangerColor' => trim(setting('theme.danger', '#b91c1c')),
            'logoUrl' => $this->logoUrl(),
        ];
    }

    private function loadProfile(Animal $animal): Animal
    {
        return $animal->load([
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
        ]);
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
