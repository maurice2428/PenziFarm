<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-3xl border border-gray-200 dark:border-white/10 bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text- shadow-xl">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Bulk Attendance Capture</h2>
                    <p class="mt-2 text-sm text-white/85 max-w-3xl">
                        Load all employees for a selected date, apply default reporting times, review statuses, and save the whole attendance sheet in one go.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <div class="text-xs uppercase tracking-widest text-white/70">Smart Daily Workflow</div>
                    <div class="mt-1 text-sm font-semibold">Fast. Clean. Operational.</div>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button
                    type="submit"
                    size="lg"
                    icon="heroicon-o-check-circle"
                >
                    Save Bulk Attendance
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
