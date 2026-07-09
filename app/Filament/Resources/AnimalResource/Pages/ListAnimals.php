<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Resources\AnimalResource;
use App\Services\AnimalImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListAnimals extends ListRecords
{
    protected static string $resource = AnimalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadAnimalImportTemplate')
                ->label('Download Import Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(
                    fn () => app(AnimalImportService::class)->downloadTemplate()
                ),

            Actions\Action::make('importAnimals')
                ->label('Import Animals')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(
                    fn (): bool => auth()->user()?->can('create animals') ?? false
                )
                ->modalHeading('Import Penzi Animals')
                ->modalDescription(
                    'Use the downloaded Excel template. Breed, location, sex, source, purpose, status and yes/no fields have dropdowns.'
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
                            'Use the Excel template for dropdowns. CSV imports are accepted but do not have dropdown lists.'
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

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Animal import completed successfully')
                        ->body($message)
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('Add New Animal'),
        ];
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
                'animal_file' => 'Please upload a valid Excel or CSV file.',
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
