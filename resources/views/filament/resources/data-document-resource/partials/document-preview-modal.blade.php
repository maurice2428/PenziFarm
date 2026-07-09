@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $path = (string) $record->file_path;
    $exists = filled($path) && Storage::disk('public')->exists($path);

    $mime = $record->mime_type;

    if (! $mime && $exists) {
        $mime = Storage::disk('public')->mimeType($path);
    }

    $lowerPath = strtolower($path);
    $type = strtolower((string) $record->document_type);

    $isImage = str_starts_with((string) $mime, 'image/') || $type === 'picture';
    $isPdf = $mime === 'application/pdf' || str_ends_with($lowerPath, '.pdf');

    $isText = str_starts_with((string) $mime, 'text/')
        || str_ends_with($lowerPath, '.txt')
        || str_ends_with($lowerPath, '.csv')
        || str_ends_with($lowerPath, '.log');

    $isExcel = str_contains((string) $mime, 'spreadsheet')
        || str_ends_with($lowerPath, '.xlsx')
        || str_ends_with($lowerPath, '.xls')
        || str_ends_with($lowerPath, '.csv');

    $isWord = str_contains((string) $mime, 'word')
        || str_ends_with($lowerPath, '.doc')
        || str_ends_with($lowerPath, '.docx');

    $openUrl = $exists ? route('data-documents.open', $record) : null;
    $downloadUrl = $exists ? route('data-documents.download', $record) : null;

    $fileName = $record->original_name ?: basename($path);
    $directory = $record->directory?->path ?? 'Uncategorized';

    $size = $record->size_bytes
        ? number_format($record->size_bytes / 1024, 2) . ' KB'
        : 'Unknown';

    $fileLabel = match (true) {
        $isImage => 'Image Preview',
        $isPdf => 'PDF Document',
        $isExcel => 'Spreadsheet / CSV',
        $isWord => 'Word Document',
        $isText => 'Text Document',
        default => 'Document',
    };
@endphp

<style>
    .document-viewer-shell {
        min-height: 82vh;
        overflow: hidden;
        border-radius: 18px;
        background: #f8fafc;
    }

    .document-viewer-toolbar {
        position: sticky;
        top: 0;
        z-index: 20;
        border-bottom: 1px solid rgba(148, 163, 184, .35);
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 14px 16px;
    }

    .document-viewer-title {
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.25;
    }

    .document-viewer-meta {
        margin-top: 4px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.45;
    }

    .document-viewer-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .document-viewer-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 10px;
        padding: 8px 11px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        transition: .15s ease;
    }

    .document-viewer-button-primary {
        background: #2563eb;
        color: #ffffff;
    }

    .document-viewer-button-primary:hover {
        background: #1d4ed8;
    }

    .document-viewer-button-success {
        background: #16a34a;
        color: #ffffff;
    }

    .document-viewer-button-success:hover {
        background: #15803d;
    }

    .document-viewer-button-gray {
        border: 1px solid rgba(148, 163, 184, .45);
        background: #ffffff;
        color: #334155;
    }

    .document-viewer-button-gray:hover {
        background: #f1f5f9;
    }

    .document-viewer-content {
        height: 78vh;
        overflow: auto;
        padding: 14px;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .06), transparent 28%),
            #eef2f7;
    }

    .document-viewer-frame {
        width: 100%;
        height: 74vh;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 14px;
        background: #ffffff;
    }

    .document-viewer-frame iframe {
        width: 100%;
        height: 100%;
        border: 0;
        background: #ffffff;
    }

    .document-image-stage {
        min-height: 74vh;
        overflow: auto;
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 14px;
        background:
            linear-gradient(45deg, #f8fafc 25%, transparent 25%),
            linear-gradient(-45deg, #f8fafc 25%, transparent 25%),
            linear-gradient(45deg, transparent 75%, #f8fafc 75%),
            linear-gradient(-45deg, transparent 75%, #f8fafc 75%);
        background-color: #ffffff;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0;
        background-size: 20px 20px;
        padding: 22px;
        text-align: center;
    }

    .document-image-stage img {
        max-width: 100%;
        max-height: none;
        border-radius: 12px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .22);
        transition: transform .18s ease;
    }

    .document-text-preview {
        max-height: 74vh;
        overflow: auto;
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 14px;
        background: #0f172a;
        padding: 18px;
    }

    .document-text-preview pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        color: #e5e7eb;
        font-size: 12px;
        line-height: 1.65;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }

    .document-unavailable-card {
        display: flex;
        min-height: 62vh;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgba(148, 163, 184, .65);
        border-radius: 16px;
        background: #ffffff;
        padding: 28px;
        text-align: center;
    }

    .document-unavailable-icon {
        margin: 0 auto 14px;
        display: flex;
        width: 70px;
        height: 70px;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        background: #eff6ff;
        color: #2563eb;
    }

    .document-unavailable-icon svg {
        width: 34px;
        height: 34px;
    }

    .document-info-grid {
        margin-top: 16px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        text-align: left;
    }

    .document-info-item {
        border-radius: 10px;
        background: #f8fafc;
        padding: 10px;
    }

    .document-info-label {
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .document-info-value {
        margin-top: 3px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .document-viewer-toolbar {
            padding: 12px;
        }

        .document-viewer-content {
            height: 76vh;
            padding: 10px;
        }

        .document-viewer-frame {
            height: 71vh;
        }

        .document-info-grid {
            grid-template-columns: 1fr;
        }
    }

    .dark .document-viewer-shell {
        background: #020617;
    }

    .dark .document-viewer-toolbar {
        border-color: rgba(148, 163, 184, .25);
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    }

    .dark .document-viewer-title,
    .dark .document-info-value {
        color: #f8fafc;
    }

    .dark .document-viewer-meta,
    .dark .document-info-label {
        color: #cbd5e1;
    }

    .dark .document-viewer-button-gray {
        border-color: rgba(148, 163, 184, .28);
        background: #111827;
        color: #e5e7eb;
    }

    .dark .document-viewer-button-gray:hover {
        background: #1f2937;
    }

    .dark .document-viewer-content {
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, .10), transparent 28%),
            #020617;
    }

    .dark .document-viewer-frame,
    .dark .document-unavailable-card,
    .dark .document-info-item {
        border-color: rgba(148, 163, 184, .25);
        background: #111827;
    }
</style>

<div
    x-data="{
        zoom: 100,
        enterFullscreen() {
            const viewer = this.$refs.viewer;

            if (viewer && viewer.requestFullscreen) {
                viewer.requestFullscreen();
            }
        }
    }"
    x-ref="viewer"
    class="document-viewer-shell"
>
    <div class="document-viewer-toolbar">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="document-viewer-title">
                    {{ $record->title }}
                </div>

                <div class="document-viewer-meta">
                    {{ $fileLabel }}
                    · {{ $fileName }}
                    · {{ $directory }}
                    · {{ $size }}
                </div>
            </div>

            <div class="document-viewer-actions">
                @if ($isImage)
                    <button
                        type="button"
                        class="document-viewer-button document-viewer-button-gray"
                        x-on:click="zoom = Math.max(50, zoom - 10)"
                    >
                        Zoom -
                    </button>

                    <button
                        type="button"
                        class="document-viewer-button document-viewer-button-gray"
                        x-on:click="zoom = 100"
                    >
                        <span x-text="zoom + '%'"></span>
                    </button>

                    <button
                        type="button"
                        class="document-viewer-button document-viewer-button-gray"
                        x-on:click="zoom = Math.min(220, zoom + 10)"
                    >
                        Zoom +
                    </button>
                @endif

                <button
                    type="button"
                    class="document-viewer-button document-viewer-button-gray"
                    x-on:click="enterFullscreen()"
                >
                    Full Screen
                </button>

                @if ($exists)
                    <a
                        href="{{ $openUrl }}"
                        target="_blank"
                        class="document-viewer-button document-viewer-button-primary"
                    >
                        Open Full Tab
                    </a>

                    <a
                        href="{{ $downloadUrl }}"
                        class="document-viewer-button document-viewer-button-success"
                    >
                        Download
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="document-viewer-content">
        @if (! $exists)
            <div class="document-unavailable-card">
                <div>
                    <div class="document-unavailable-icon text-danger-600">
                        <x-heroicon-o-exclamation-triangle />
                    </div>

                    <div class="text-base font-black text-gray-950 dark:text-white">
                        File missing from storage
                    </div>

                    <p class="mt-2 max-w-xl text-sm text-gray-600 dark:text-gray-300">
                        The database record exists, but the uploaded file was not found in the public storage disk.
                    </p>

                    <div class="document-info-grid">
                        <div class="document-info-item">
                            <div class="document-info-label">Stored Path</div>
                            <div class="document-info-value">{{ $path ?: 'Not recorded' }}</div>
                        </div>

                        <div class="document-info-item">
                            <div class="document-info-label">Document Type</div>
                            <div class="document-info-value">{{ $record->document_type }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($isImage)
            <div class="document-image-stage">
                <img
                    src="{{ $openUrl }}"
                    alt="{{ $record->title }}"
                    x-bind:style="'transform: scale(' + (zoom / 100) + '); transform-origin: top center;'"
                />
            </div>
        @elseif ($isPdf)
            <div class="document-viewer-frame">
                <iframe
                    src="{{ $openUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                    allowfullscreen
                ></iframe>
            </div>
        @elseif ($isText)
            @php
                $content = Storage::disk('public')->get($path);
                $content = mb_substr($content, 0, 50000);
            @endphp

            <div class="document-text-preview">
                <pre>{{ $content }}</pre>
            </div>
        @else
            <div class="document-unavailable-card">
                <div class="max-w-2xl">
                    <div class="document-unavailable-icon">
                        @if ($isExcel)
                            <x-heroicon-o-table-cells />
                        @elseif ($isWord)
                            <x-heroicon-o-document-text />
                        @else
                            <x-heroicon-o-document />
                        @endif
                    </div>

                    <div class="text-base font-black text-gray-950 dark:text-white">
                        Browser preview is limited for this file type
                    </div>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Word and Excel files usually cannot be rendered directly inside a local browser preview.
                        Use Open Full Tab or Download to view the file using the correct application.
                    </p>

                    <div class="document-info-grid">
                        <div class="document-info-item">
                            <div class="document-info-label">File Name</div>
                            <div class="document-info-value">{{ $fileName }}</div>
                        </div>

                        <div class="document-info-item">
                            <div class="document-info-label">MIME Type</div>
                            <div class="document-info-value">{{ $mime ?: 'Unknown' }}</div>
                        </div>

                        <div class="document-info-item">
                            <div class="document-info-label">Directory</div>
                            <div class="document-info-value">{{ $directory }}</div>
                        </div>

                        <div class="document-info-item">
                            <div class="document-info-label">Storage Path</div>
                            <div class="document-info-value">{{ $path }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
