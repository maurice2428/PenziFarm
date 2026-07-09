<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent>
            {{ $this->form }}
        </form>

        @php
            $filterKey = md5(json_encode($this->filters));
            $widgets = $this->getWidgets();
        @endphp

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach ($widgets as $widget)
                <div
                    wire:key="sales-widget-{{ str($widget)->afterLast('\\')->kebab() }}-{{ $filterKey }}"
                    @class([
                        'xl:col-span-2' => str_contains($widget, 'Stats') || str_contains($widget, 'Table'),
                    ])
                >
                    @livewire($widget, ['filters' => $this->filters], key($widget . '-' . $filterKey))
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
