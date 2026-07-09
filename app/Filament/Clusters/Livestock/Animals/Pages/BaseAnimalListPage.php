<?php

namespace App\Filament\Clusters\Livestock\Animals\Pages;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\AnimalResource;
use App\Services\AnimalImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

abstract class BaseAnimalListPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = AnimalsCluster::class;

    protected static string $view = 'filament.clusters.livestock.animals.pages.table-page';

    abstract protected function getFilteredQuery(): Builder;

    public function table(Table $table): Table
    {
        return AnimalResource::getAnimalTable(
            $table,
            $this->isArchivedView()
        )->query(
            $this->getFilteredQuery()
        );
    }

    protected function isArchivedView(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadAnimalImportTemplate')
                ->label('Download Import Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => $this->canImportAnimals())
                ->action(
                    fn () => app(AnimalImportService::class)->downloadTemplate()
                ),

            Actions\Action::make('importAnimals')
                ->label('Import Animals')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn (): bool => $this->canImportAnimals())
                ->modalHeading('Import Penzi Animals')
                ->modalDescription(
                    'Download the Excel template first. It contains dropdowns for Breed, Sex, Source, Purpose, Status, Is Breeder, Sale Ready and Location. Tags are generated automatically from Breed and Date of Birth.'
                )
                ->modalSubmitActionLabel('Import Animals')
                ->form([
                    Forms\Components\FileUpload::make('animal_file')
                        ->label('Animal Import File')
                        ->required()
                        ->disk('local')
                        ->directory('imports/animals')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                            'application/csv',
                        ])
                        ->maxSize(10240)
                        ->helperText(
                            'Use the downloaded Excel template. Existing animals will not be changed or deleted.'
                        ),
                ])
                ->action(function (array $data): void {
                    $uploadedFile = $data['animal_file'] ?? null;

                    $filePath = $this->resolveImportPath($uploadedFile);

                    try {
                        $result = app(AnimalImportService::class)->import(
                            $filePath,
                            auth()->id()
                        );
                    } finally {
                        if (is_string($uploadedFile)) {
                            Storage::disk('local')->delete($uploadedFile);
                        }
                    }

                    $message = "Created {$result['created']} animal(s).";

                    if ($result['failed'] > 0) {
                        $message .= " {$result['failed']} row(s) failed.";

                        $shownErrors = array_slice($result['errors'], 0, 8);

                        if ($shownErrors !== []) {
                            $message .= ' ' . implode(' | ', $shownErrors);
                        }

                        if (count($result['errors']) > 8) {
                            $message .= ' Additional errors were omitted from this notification.';
                        }

                        Notification::make()
                            ->warning()
                            ->title('Animal import completed with some errors')
                            ->body(Str::limit($message, 1800))
                            ->persistent()
                            ->send();

                        $this->resetTable();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Animal import completed successfully')
                        ->body($message)
                        ->send();

                    $this->resetTable();
                }),

            Actions\Action::make('add')
                ->label('Add New Animal')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(
                    fn (): bool => auth()->user()?->can('create animals') ?? false
                )
                ->url(AnimalResource::getUrl('create')),
        ];
    }

    /**
     * Import actions should only appear in the All Animals cluster page.
     */
    protected function canImportAnimals(): bool
    {
        return $this instanceof AllAnimals
            && (auth()->user()?->can('create animals') ?? false);
    }

    private function resolveImportPath(mixed $uploadedFile): string
    {
        if ($uploadedFile instanceof TemporaryUploadedFile) {
            return $uploadedFile->getRealPath();
        }

        if (is_array($uploadedFile)) {
            $uploadedFile = reset($uploadedFile);
        }

        if (! is_string($uploadedFile) || blank($uploadedFile)) {
            throw ValidationException::withMessages([
                'animal_file' => 'Please upload a valid Excel or CSV import file.',
            ]);
        }

        $path = Storage::disk('local')->path($uploadedFile);

        if (! is_file($path)) {
            throw ValidationException::withMessages([
                'animal_file' => 'The uploaded import file could not be found.',
            ]);
        }

        return $path;
    }
}
