<x-filament-panels::page>
    @php
        $animal = $record->animal;

        $weights = \App\Models\AnimalWeight::query()
            ->with(['recorder'])
            ->where('animal_id', $record->animal_id)
            ->orderBy('recorded_at')
            ->get();

        $latest = $weights->last();
        $first = $weights->first();

        $totalGain = $first && $latest
            ? (float) $latest->weight_kg - (float) $first->weight_kg
            : 0;

        $highest = $weights->max('weight_kg');
        $lowest = $weights->min('weight_kg');
        $average = $weights->avg('weight_kg');

        $labels = $weights->map(fn ($w) => $w->recorded_at?->format('d M Y, h:i A'))->values();
        $data = $weights->map(fn ($w) => (float) $w->weight_kg)->values();

        $primary = trim(setting('theme.primary') ?? '#008f00');
    @endphp

    <style>
        .animal-weight-page {
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
        }

        .aw-hero {
            background: linear-gradient(135deg, {{ $primary }}, #064e3b, #0f172a);
            border-radius: 24px;
            padding: 28px;
            color: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, .18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .aw-eyebrow {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #dcfce7;
        }

        .aw-title {
            margin-top: 8px;
            font-size: 36px;
            font-weight: 950;
            line-height: 1;
            color: #fff;
        }

        .aw-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .aw-tag {
            padding: 7px 13px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.22);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .aw-latest {
            min-width: 250px;
            text-align: right;
            border-radius: 20px;
            padding: 22px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.24);
        }

        .aw-latest-label {
            font-size: 12px;
            font-weight: 900;
            color: #bbf7d0;
            text-transform: uppercase;
        }

        .aw-latest-value {
            margin-top: 8px;
            font-size: 34px;
            font-weight: 950;
            color: #fff;
        }

        .aw-latest-date {
            margin-top: 5px;
            font-size: 12px;
            color: #dcfce7;
            font-weight: 700;
        }

        .aw-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
        }

        .aw-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        }

        .aw-card-label {
            color: #475569;
            font-size: 13px;
            font-weight: 900;
        }

        .aw-card-value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 30px;
            font-weight: 950;
            line-height: 1;
        }

        .aw-card-note {
            margin-top: 8px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .aw-card-success {
            background: linear-gradient(135deg, #ecfdf5, #ffffff);
            border-color: #a7f3d0;
        }

        .aw-card-danger {
            background: linear-gradient(135deg, #fef2f2, #ffffff);
            border-color: #fecaca;
        }

        .aw-section {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .09);
        }

        .aw-section-header {
            padding: 22px 26px;
            background: linear-gradient(135deg, #f8fafc, #ecfdf5);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .aw-section-title {
            margin: 0;
            color: #0f172a !important;
            font-size: 22px;
            font-weight: 950;
        }

        .aw-section-subtitle {
            margin-top: 4px;
            color: #475569 !important;
            font-size: 14px;
            font-weight: 650;
        }

        .aw-search {
            width: 320px;
            max-width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 10px 16px;
            color: #0f172a;
            font-weight: 700;
            background: #ffffff;
            outline: none;
        }

        .aw-search:focus {
            border-color: {{ $primary }};
            box-shadow: 0 0 0 4px rgba(0,143,0,.12);
        }

        .aw-chart-wrap {
            padding: 22px;
        }

        .aw-chart-box {
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            border-radius: 18px;
            padding: 18px;
        }

        .aw-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .aw-table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .aw-table thead th {
            background: #0f172a;
            color: #f8fafc;
            padding: 15px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }

        .aw-table thead th:hover {
            background: #1e293b;
        }

        .aw-table tbody td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            color: #334155;
            font-weight: 700;
            vertical-align: middle;
            white-space: nowrap;
        }

        .aw-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .aw-table tbody tr:hover {
            background: #ecfdf5;
        }

        .aw-number {
            color: #64748b !important;
            font-weight: 950 !important;
        }

        .aw-weight {
            color: #0f172a !important;
            font-size: 16px;
            font-weight: 950 !important;
        }

        .aw-remarks {
            white-space: normal !important;
            min-width: 220px;
        }

        .aw-badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 950;
            white-space: nowrap;
        }

        .aw-badge-gain {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .aw-badge-loss {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .aw-badge-stable {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .aw-badge-first {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .aw-empty {
            padding: 34px !important;
            text-align: center;
            color: #64748b !important;
            font-weight: 900;
        }

        @media (max-width: 1200px) {
            .aw-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .aw-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .aw-latest {
                width: 100%;
                text-align: left;
            }

            .aw-stats {
                grid-template-columns: 1fr;
            }

            .aw-title {
                font-size: 28px;
            }
        }
    </style>

    <div class="animal-weight-page">

        {{-- HERO --}}
        <div class="aw-hero">
            <div>
                <div class="aw-eyebrow">Animal Weight Intelligence</div>

                <div class="aw-title">
                    {{ $animal?->tag_number ?? 'Unknown Animal' }}
                </div>

                <div class="aw-tags">
                    <span class="aw-tag">{{ $animal?->breed?->breed_name ?? 'Unknown Breed' }}</span>
                    <span class="aw-tag">{{ $animal?->species ?? '-' }}</span>
                    <span class="aw-tag">{{ $animal?->sex ?? '-' }}</span>
                    <span class="aw-tag">{{ $animal?->status ?? '-' }}</span>
                </div>
            </div>

            <div class="aw-latest">
                <div class="aw-latest-label">Latest Weight</div>
                <div class="aw-latest-value">
                    {{ number_format((float) $latest?->weight_kg, 2) }} KG
                </div>
                <div class="aw-latest-date">
                    {{ $latest?->recorded_at?->format('d M Y, h:i A') ?? '-' }}
                </div>
            </div>
        </div>

        {{-- STATS --}}
        <div class="aw-stats">
            <div class="aw-card">
                <div class="aw-card-label">Total Records</div>
                <div class="aw-card-value">{{ $weights->count() }}</div>
                <div class="aw-card-note">All weight entries</div>
            </div>

            <div class="aw-card">
                <div class="aw-card-label">Average Weight</div>
                <div class="aw-card-value">{{ number_format((float) $average, 2) }} KG</div>
                <div class="aw-card-note">Across all records</div>
            </div>

            <div class="aw-card">
                <div class="aw-card-label">Highest Weight</div>
                <div class="aw-card-value">{{ number_format((float) $highest, 2) }} KG</div>
                <div class="aw-card-note">Best recorded weight</div>
            </div>

            <div class="aw-card">
                <div class="aw-card-label">Lowest Weight</div>
                <div class="aw-card-value">{{ number_format((float) $lowest, 2) }} KG</div>
                <div class="aw-card-note">Lowest recorded weight</div>
            </div>

            <div class="aw-card {{ $totalGain >= 0 ? 'aw-card-success' : 'aw-card-danger' }}">
                <div class="aw-card-label">Net Movement</div>
                <div class="aw-card-value">
                    {{ $totalGain >= 0 ? '+' : '' }}{{ number_format($totalGain, 2) }} KG
                </div>
                <div class="aw-card-note">From first to latest record</div>
            </div>
        </div>

        {{-- CHART --}}
        <div class="aw-section">
            <div class="aw-section-header">
                <div>
                    <h2 class="aw-section-title">Weight Trend Over Time</h2>
                    <div class="aw-section-subtitle">
                        Visual growth movement for {{ $animal?->tag_number }}
                    </div>
                </div>
            </div>

            <div class="aw-chart-wrap">
                <div class="aw-chart-box">
                    <canvas id="weightTrendChart" height="95"></canvas>
                </div>
            </div>
        </div>

        {{-- SEARCHABLE / SORTABLE TABLE --}}
        <div class="aw-section">
            <div class="aw-section-header">
                <div>
                    <h2 class="aw-section-title">Weight History Register</h2>
                    <div class="aw-section-subtitle">
                        Complete chronological record of all weight entries for this animal.
                    </div>
                </div>

                <input
                    type="text"
                    id="weightSearch"
                    class="aw-search"
                    placeholder="Search date, weight, recorder, remarks..."
                >
            </div>

            <div class="aw-table-wrap">
                <table class="aw-table" id="weightHistoryTable">
                    <thead>
                        <tr>
                            <th data-type="number">#</th>
                            <th data-type="date">Date Recorded</th>
                            <th data-type="number">Current Weight</th>
                            <th data-type="number">Previous Weight</th>
                            <th data-type="text">Movement</th>
                            <th data-type="text">Recorded By</th>
                            <th data-type="text">Remarks</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($weights->sortByDesc('recorded_at')->values() as $index => $weight)
                            @php
                                $movementText = 'First Entry';

                                if ($weight->trend === 'gaining') {
                                    $movementText = 'Gained ' . number_format(abs($weight->weight_difference), 2) . ' KG';
                                } elseif ($weight->trend === 'losing') {
                                    $movementText = 'Lost ' . number_format(abs($weight->weight_difference), 2) . ' KG';
                                } elseif ($weight->trend === 'stable') {
                                    $movementText = 'No Change';
                                }
                            @endphp

                            <tr>
                                <td class="aw-number" data-sort="{{ $weights->count() - $index }}">
                                    {{ $weights->count() - $index }}
                                </td>

                                <td data-sort="{{ $weight->recorded_at?->timestamp ?? 0 }}">
                                    {{ $weight->recorded_at?->format('d M Y, h:i A') }}
                                </td>

                                <td class="aw-weight" data-sort="{{ (float) $weight->weight_kg }}">
                                    {{ number_format((float) $weight->weight_kg, 2) }} KG
                                </td>

                                <td data-sort="{{ $weight->previous_weight_kg !== null ? (float) $weight->previous_weight_kg : -1 }}">
                                    {{ $weight->previous_weight_kg ? number_format((float) $weight->previous_weight_kg, 2) . ' KG' : 'First Entry' }}
                                </td>

                                <td data-sort="{{ $movementText }}">
                                    @if ($weight->trend === 'gaining')
                                        <span class="aw-badge aw-badge-gain">
                                            ↑ Gained {{ number_format(abs($weight->weight_difference), 2) }} KG
                                        </span>
                                    @elseif ($weight->trend === 'losing')
                                        <span class="aw-badge aw-badge-loss">
                                            ↓ Lost {{ number_format(abs($weight->weight_difference), 2) }} KG
                                        </span>
                                    @elseif ($weight->trend === 'stable')
                                        <span class="aw-badge aw-badge-stable">
                                            — No Change
                                        </span>
                                    @else
                                        <span class="aw-badge aw-badge-first">
                                            + First Entry
                                        </span>
                                    @endif
                                </td>

                                <td data-sort="{{ $weight->recorder?->name ?? 'System' }}">
                                    {{ $weight->recorder?->name ?? 'System' }}
                                </td>

                                <td class="aw-remarks" data-sort="{{ $weight->remarks ?: '-' }}">
                                    {{ $weight->remarks ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="aw-empty">
                                    No weight records found for this animal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartElement = document.getElementById('weightTrendChart');

            if (chartElement) {
                new Chart(chartElement, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: 'Weight KG',
                            data: @json($data),
                            borderColor: @json($primary),
                            backgroundColor: 'rgba(0, 143, 0, 0.16)',
                            pointBackgroundColor: @json($primary),
                            pointBorderColor: '#0f172a',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#334155',
                                    font: { weight: 'bold' }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#ffffff',
                                bodyColor: '#e2e8f0',
                                borderColor: @json($primary),
                                borderWidth: 1,
                                callbacks: {
                                    label: function (context) {
                                        return 'Weight: ' + context.parsed.y + ' KG';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#64748b',
                                    font: { weight: 'bold' }
                                },
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    color: '#64748b',
                                    font: { weight: 'bold' },
                                    callback: function (value) {
                                        return value + ' KG';
                                    }
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.22)'
                                }
                            }
                        }
                    }
                });
            }

            const searchInput = document.getElementById('weightSearch');
            const table = document.getElementById('weightHistoryTable');

            if (!table) return;

            const tbody = table.querySelector('tbody');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const value = this.value.toLowerCase();

                    tbody.querySelectorAll('tr').forEach(row => {
                        row.style.display = row.innerText.toLowerCase().includes(value)
                            ? ''
                            : 'none';
                    });
                });
            }

            table.querySelectorAll('thead th').forEach((header, index) => {
                header.dataset.direction = 'desc';

                header.addEventListener('click', function () {
                    const type = this.dataset.type || 'text';
                    const direction = this.dataset.direction === 'asc' ? 'desc' : 'asc';

                    table.querySelectorAll('thead th').forEach(th => {
                        th.dataset.direction = 'desc';
                        th.innerText = th.innerText.replace(' ↑', '').replace(' ↓', '');
                    });

                    this.dataset.direction = direction;
                    this.innerText = this.innerText + (direction === 'asc' ? ' ↑' : ' ↓');

                    const rows = Array.from(tbody.querySelectorAll('tr'))
                        .filter(row => row.children.length > 1);

                    rows.sort((a, b) => {
                        let aValue = a.children[index]?.dataset.sort ?? a.children[index]?.innerText ?? '';
                        let bValue = b.children[index]?.dataset.sort ?? b.children[index]?.innerText ?? '';

                        if (type === 'number' || type === 'date') {
                            aValue = parseFloat(aValue) || 0;
                            bValue = parseFloat(bValue) || 0;
                        } else {
                            aValue = aValue.toString().toLowerCase();
                            bValue = bValue.toString().toLowerCase();
                        }

                        if (aValue < bValue) return direction === 'asc' ? -1 : 1;
                        if (aValue > bValue) return direction === 'asc' ? 1 : -1;
                        return 0;
                    });

                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
    </script>
</x-filament-panels::page>
