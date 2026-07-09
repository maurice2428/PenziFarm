<?php

namespace App\Filament\Resources\DataDocumentResource\Pages;

use App\Filament\Resources\DataDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDataDocument extends EditRecord
{
    protected static string $resource = DataDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['file_path']) && Storage::disk('public')->exists($data['file_path'])) {
            $data['original_name'] = basename($data['file_path']);
            $data['mime_type'] = Storage::disk('public')->mimeType($data['file_path']);
            $data['size_bytes'] = Storage::disk('public')->size($data['file_path']);
        }

        return $data;
    }
}
