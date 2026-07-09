<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-2">
            @if (filled(static::$heading))
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ static::$heading }}
                    </h2>

                    @if (filled(static::$description))
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ static::$description }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="overflow-x-auto">
                <div
                    style="
                        min-width: {{ max(1200, count($this->getCachedData()['labels'] ?? []) * 90) }}px;
                        height: 420px;
                    "
                >
                    <canvas
                        x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                        x-ref="canvas"
                    ></canvas>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
