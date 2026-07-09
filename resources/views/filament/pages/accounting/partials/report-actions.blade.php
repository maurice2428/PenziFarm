@props([
    'report',
    'params' => [],
    'printLabel' => 'Print View',
    'pdfLabel' => 'Download PDF',
])

@php
    use Illuminate\Support\Facades\Route;

    $printRoute = "accounting.reports.{$report}.print";
    $pdfRoute = "accounting.reports.{$report}.pdf";

    $cleanParams = collect($params)
        ->filter(fn ($value) => filled($value))
        ->all();
@endphp

<div class="lw-report-actions">
    @if (Route::has($printRoute))
        <a
            href="{{ route($printRoute, $cleanParams) }}"
            target="_blank"
            class="lw-report-action lw-report-action-soft"
        >
            <x-heroicon-o-printer class="h-4 w-4" />
            <span>{{ $printLabel }}</span>
        </a>
    @endif

    @if (Route::has($pdfRoute))
        <a
            href="{{ route($pdfRoute, $cleanParams) }}"
            target="_blank"
            class="lw-report-action lw-report-action-primary"
        >
            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
            <span>{{ $pdfLabel }}</span>
        </a>
    @endif
</div>
