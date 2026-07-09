<?php

namespace App\Filament\Resources\DataDocumentResource\Pages;

use App\Filament\Resources\DataDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDataDocument extends CreateRecord
{
    protected static string $resource = DataDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by_user_id'] = auth()->id();

        if (! empty($data['file_path']) && Storage::disk('public')->exists($data['file_path'])) {
            $data['original_name'] = basename($data['file_path']);
            $data['mime_type'] = Storage::disk('public')->mimeType($data['file_path']);
            $data['size_bytes'] = Storage::disk('public')->size($data['file_path']);
        }

        return $data;
    }
}
