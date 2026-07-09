@php
    /** @var \App\Models\CropSeason $record */
    $record = $getRecord();

    $insight = $record->stage_insight;
    $urgency = $record->visual_urgency;

    $badgeClass = match ($urgency) {
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300',
    };
@endphp

<div class="flex items-center gap-3 min-w-[260px]">
    <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-gray-100 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        <img
            src="{{ $record->stage_image_url }}"
            alt="{{ $record->name }}"
            class="h-full w-full object-cover"
        >

        @if ($record->has_stage_model)
            <div class="absolute bottom-1 right-1 rounded-full bg-black/70 px-1.5 py-0.5 text-[9px] font-bold text-white">
                3D
            </div>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <div class="truncate text-xs font-bold text-gray-950 dark:text-white">
                {{ $insight['stage_label'] ?? str($record->growth_stage)->replace('_', ' ')->title() }}
            </div>

            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badgeClass }}">
                {{ $insight['urgency_label'] ?? 'Monitor' }}
            </span>
        </div>

        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div
                class="h-full rounded-full bg-emerald-500"
                style="width: {{ $record->growth_progress_percent }}%;"
            ></div>
        </div>

        <div class="mt-1 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
            <span>{{ $record->growth_progress_percent }}% progress</span>
            <span>{{ $record->harvest_status }}</span>
        </div>
    </div>
</div>
