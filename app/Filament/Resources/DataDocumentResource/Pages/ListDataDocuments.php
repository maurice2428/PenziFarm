<?php

namespace App\Filament\Resources\DataDocumentResource\Pages;

use App\Filament\Resources\DataDocumentResource;
use App\Models\DataDocument;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListDataDocuments extends ListRecords
{
    protected static string $resource = DataDocumentResource::class;

    protected static string $view = 'filament.resources.data-document-resource.pages.list-data-documents';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Data Document')
                ->visible(fn (): bool => auth()->user()?->can('create data documents') ?? false),

            Actions\Action::make('repairMetadata')
                ->label('Repair File Metadata')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Repair document file metadata?')
                ->modalDescription('This will recalculate missing file size, MIME type and original filename for existing uploaded documents.')
                ->action(function (): void {
                    $updated = 0;
                    $missing = 0;

                    DataDocument::query()
                        ->whereNotNull('file_path')
                        ->get()
                        ->each(function (DataDocument $document) use (&$updated, &$missing): void {
                            if (! Storage::disk('public')->exists($document->file_path)) {
                                $missing++;

                                return;
                            }

                            $document->update([
                                'original_name' => $document->original_name ?: basename($document->file_path),
                                'mime_type' => Storage::disk('public')->mimeType($document->file_path),
                                'size_bytes' => Storage::disk('public')->size($document->file_path),
                            ]);

                            $updated++;
                        });

                    Notification::make()
                        ->success()
                        ->title('Metadata repaired')
                        ->body("Updated {$updated} document(s). Missing files: {$missing}.")
                        ->send();

                    $this->resetTable();
                }),
        ];
    }
}
