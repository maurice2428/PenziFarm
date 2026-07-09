<x-filament-panels::page>
    <style>
        /*
         * Fix visible Filament screen-reader labels like:
         * "Select/deselect item 2 for bulk actions."
         */
        .fi-ta .sr-only,
        .fi-ta-selection-cell .sr-only,
        .fi-ta-header-cell .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border-width: 0 !important;
        }

        .data-document-preview-thumb {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(148, 163, 184, .35);
            background: #f8fafc;
        }

        .data-document-file-card {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
        }

        .data-document-file-card svg {
            width: 25px;
            height: 25px;
        }

        .dark .data-document-file-card {
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            border-color: rgba(148, 163, 184, .25);
            color: #cbd5e1;
        }
    </style>

    {{ $this->table }}
</x-filament-panels::page>
