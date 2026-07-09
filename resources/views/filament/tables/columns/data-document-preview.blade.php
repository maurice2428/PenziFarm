@php
    use App\Models\DataDocument;
    use Illuminate\Support\Facades\Storage;

    $document = $getRecord();

    $path = $document instanceof DataDocument
        ? (string) $document->file_path
        : '';

    $exists = filled($path) && Storage::disk('public')->exists($path);

    $mime = $document instanceof DataDocument
        ? $document->mime_type
        : null;

    if (! $mime && $exists) {
        $mime = Storage::disk('public')->mimeType($path);
    }

    $lowerPath = strtolower($path);

    $type = $document instanceof DataDocument
        ? strtolower((string) $document->document_type)
        : '';

    $isImage = str_starts_with((string) $mime, 'image/') || $type === 'picture';
    $isPdf = $mime === 'application/pdf' || str_ends_with($lowerPath, '.pdf');

    $isExcel = str_contains((string) $mime, 'spreadsheet')
        || str_ends_with($lowerPath, '.xlsx')
        || str_ends_with($lowerPath, '.xls')
        || str_ends_with($lowerPath, '.csv');

    $isWord = str_contains((string) $mime, 'word')
        || str_ends_with($lowerPath, '.doc')
        || str_ends_with($lowerPath, '.docx');
@endphp

<div class="flex justify-center">
    @if (! $document instanceof DataDocument)
        <div class="data-document-file-card" title="Invalid record">
            <x-heroicon-o-exclamation-triangle />
        </div>
    @elseif (! $exists)
        <div class="data-document-file-card text-danger-600 dark:text-danger-400" title="File missing">
            <x-heroicon-o-exclamation-triangle />
        </div>
    @elseif ($isImage)
        <img
            src="{{ route('data-documents.open', $document) }}"
            alt="{{ $document->title }}"
            class="data-document-preview-thumb"
        />
    @elseif ($isPdf)
        <div class="data-document-file-card text-danger-600 dark:text-danger-400" title="PDF document">
            <x-heroicon-o-document />
        </div>
    @elseif ($isExcel)
        <div class="data-document-file-card text-primary-600 dark:text-primary-400" title="Excel or CSV file">
            <x-heroicon-o-table-cells />
        </div>
    @elseif ($isWord)
        <div class="data-document-file-card text-info-600 dark:text-info-400" title="Word document">
            <x-heroicon-o-document-text />
        </div>
    @else
        <div class="data-document-file-card" title="Document">
            <x-heroicon-o-document-text />
        </div>
    @endif
</div>
