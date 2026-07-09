<div class="w-full max-w-2xl">
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            placeholder="Search Here... (Ctrl + K)"
            class="w-full rounded-xl border px-4 py-2 text-sm shadow-sm"
        >

        @if($this->query !== '')
            <div class="absolute z-50 mt-2 w-full bg-white border rounded-xl shadow-lg">
                @forelse($this->results as $result)
                    <button
                        wire:click="openResult('{{ $result['url'] }}', '{{ addslashes($result['title']) }}')"
                        class="w-full text-left px-4 py-2 hover:bg-gray-100"
                    >
                        <div class="font-semibold">{{ $result['title'] }}</div>
                        <div class="text-xs text-gray-500">{{ $result['subtitle'] }}</div>
                    </button>
                @empty
                    <div class="px-4 py-2 text-sm text-gray-500">
                        No results found
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>
