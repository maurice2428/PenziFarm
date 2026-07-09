<?php

namespace App\Http\Controllers;

use App\Models\DataDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataDocumentFileController extends Controller
{
    public function open(DataDocument $document)
    {
        abort_unless(auth()->user()?->can('view data documents'), 403);

        if (! $this->fileExists($document)) {
            abort(404, 'The document record exists, but the uploaded file was not found.');
        }

        $fileName = $this->safeFileName($document);

        return Storage::disk('public')->response(
            $document->file_path,
            $fileName,
            [
                'Content-Type' => Storage::disk('public')->mimeType($document->file_path) ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    public function download(DataDocument $document)
    {
        abort_unless(auth()->user()?->can('download data documents'), 403);

        if (! $this->fileExists($document)) {
            abort(404, 'The document record exists, but the uploaded file was not found.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $this->safeFileName($document)
        );
    }

    private function fileExists(DataDocument $document): bool
    {
        return filled($document->file_path)
            && Storage::disk('public')->exists($document->file_path);
    }

    private function safeFileName(DataDocument $document): string
    {
        $name = $document->original_name ?: basename((string) $document->file_path);

        $name = trim((string) $name);

        return $name !== ''
            ? Str::ascii($name)
            : 'document-' . $document->getKey();
    }
}
