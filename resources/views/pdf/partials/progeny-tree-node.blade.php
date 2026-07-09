@php
    $color = match ($node['sex'] ?? null) {
        'Male' => '#2563eb',
        'Female' => '#be185d',
        default => '#64748b',
    };
@endphp

<div class="pdf-tree-node">
    <table
        class="pdf-node-card"
        cellspacing="0"
        cellpadding="0"
        style="border-left-color: {{ $color }};"
    >
        <tr>
            <td class="pdf-node-main">
                <div class="pdf-node-tag">{{ $node['tag_number'] }}</div>
                <div class="pdf-node-detail">
                    {{ $node['breed'] ?? 'Unknown breed' }} |
                    {{ $node['sex'] ?? '-' }} |
                    {{ $node['status'] ?? '-' }}

                    @if (($node['purity'] ?? null) !== null)
                        | {{ number_format((float) $node['purity'], 2) }}% purity
                    @endif
                </div>

                <div class="pdf-node-detail">
                    DOB: {{ $node['date_of_birth'] ?? 'Not recorded' }} |
                    Location: {{ $node['location'] ?? 'Not recorded' }}
                    @if (! empty($node['is_breeder']))
                        | Breeding stock
                    @endif
                </div>

                @if (! empty($node['circular']))
                    <div class="pdf-node-detail" style="color: #b45309;">
                        Repeated lineage reference - branch stopped to prevent a loop.
                    </div>
                @endif
            </td>

            <td class="pdf-node-generation" style="color: {{ $color }};">
                G{{ $node['level'] }}
            </td>
        </tr>
    </table>

    @if (! empty($node['children']))
        <div class="pdf-children">
            @foreach ($node['children'] as $child)
                <table class="pdf-child-row" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="pdf-connector"></td>
                        <td class="pdf-child-content">
                            @include('pdf.partials.progeny-tree-node', ['node' => $child])
                        </td>
                    </tr>
                </table>
            @endforeach
        </div>
    @endif
</div>
