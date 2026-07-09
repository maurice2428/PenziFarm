@php
    $nodeColor = match ($node['sex']) {
        'Male' => '#2563eb',
        'Female' => '#db2777',
        default => '#64748b',
    };
@endphp

<div class="progeny-tree-node" style="--node-color: {{ $nodeColor }};">
    <div class="progeny-node-card {{ $node['circular'] ? 'is-circular' : '' }}">
        <div class="progeny-node-top">
            <div class="progeny-node-icon">
                @if ($node['sex'] === 'Male')
                    <x-heroicon-o-shield-check class="h-4 w-4" />
                @else
                    <x-heroicon-o-heart class="h-4 w-4" />
                @endif
            </div>

            <div class="progeny-node-main">
                <div class="progeny-node-tag">{{ $node['tag_number'] }}</div>
                <div class="progeny-node-breed">{{ $node['breed'] ?? 'Unknown breed' }}</div>
            </div>

            <div class="progeny-node-generation">G{{ $node['level'] }}</div>
        </div>

        <div class="progeny-node-meta">
            <span>{{ $node['sex'] }}</span>
            <span>{{ $node['status'] }}</span>
            @if ($node['purity'] !== null)
                <span>{{ number_format($node['purity'], 2) }}%</span>
            @endif
            @if ($node['date_of_birth'])
                <span>{{ $node['date_of_birth'] }}</span>
            @endif
        </div>

        @if ($node['is_breeder'])
            <div class="progeny-node-badge">Breeding stock</div>
        @endif

        @if ($node['circular'])
            <div class="progeny-node-warning">Repeated lineage reference</div>
        @endif
    </div>

    @if (! empty($node['children']))
        <div class="progeny-children">
            @foreach ($node['children'] as $child)
                <div class="progeny-child-branch">
                    @include('filament.pages.partials.progeny-tree-node', ['node' => $child])
                </div>
            @endforeach
        </div>
    @endif
</div>
