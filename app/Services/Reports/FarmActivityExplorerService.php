<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FarmActivityExplorerService
{
    public function build(?string $from = null, ?string $to = null): array
    {
        [$fromDate, $toDate] = $this->normalisePeriod($from, $to);

        if (! Schema::hasTable('animals')) {
            return $this->emptyReport($fromDate, $toDate, 'The animals table was not found.');
        }

        $columns = $this->animalColumns();
        $statusColumn = $columns['status'];
        $createdColumn = $columns['created_at'];
        $tagColumn = $columns['tag'];
        $breedColumn = $columns['breed'];
        $genderColumn = $columns['gender'];
        $archivedColumn = $columns['archived'];
        $breederColumn = $columns['breeder'];

        $asAtQuery = DB::table('animals');

        if ($createdColumn) {
            $asAtQuery->where($createdColumn, '<=', $toDate->copy()->endOfDay());
        }

        $totalAsAt = (int) (clone $asAtQuery)->count();

        $newInPeriod = 0;
        if ($createdColumn) {
            $newInPeriod = (int) DB::table('animals')
                ->whereBetween($createdColumn, [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()])
                ->count();
        }

        $rawStatusCounts = [];
        if ($statusColumn) {
            $rawStatusCounts = (clone $asAtQuery)
                ->select($statusColumn . ' as status', DB::raw('COUNT(*) as total'))
                ->groupBy($statusColumn)
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(function ($row) {
                    $status = $this->normaliseStatus((string) ($row->status ?: 'Unknown'));

                    return [$status => (int) $row->total];
                })
                ->all();
        } else {
            $rawStatusCounts = ['unknown' => $totalAsAt];
        }

        $active = $this->sumStatuses($rawStatusCounts, ['active', 'current', 'alive']);
        $dead = $this->sumStatuses($rawStatusCounts, ['dead', 'mortality', 'died']);
        $culled = $this->sumStatuses($rawStatusCounts, ['culled', 'cull', 'dead culled', 'dead-culled']);
        $sold = $this->sumStatuses($rawStatusCounts, ['sold']);
        $unknown = max(0, $totalAsAt - ($active + $dead + $culled + $sold));

        $archived = 0;
        if ($archivedColumn) {
            $archived = (int) (clone $asAtQuery)
                ->where(function ($query) use ($archivedColumn) {
                    $query->where($archivedColumn, 1)
                        ->orWhere($archivedColumn, true)
                        ->orWhere($archivedColumn, '1');
                })
                ->count();
        }

        $breeders = 0;
        if ($breederColumn) {
            $breeders = (int) (clone $asAtQuery)
                ->where(function ($query) use ($breederColumn) {
                    $query->where($breederColumn, 1)
                        ->orWhere($breederColumn, true)
                        ->orWhere($breederColumn, '1');
                })
                ->count();
        }

        $mortalityRate = $totalAsAt > 0 ? round(($dead / $totalAsAt) * 100, 2) : 0;
        $saleRate = $totalAsAt > 0 ? round(($sold / $totalAsAt) * 100, 2) : 0;
        $activeRate = $totalAsAt > 0 ? round(($active / $totalAsAt) * 100, 2) : 0;
        $productivePool = max(0, $active - $archived);

        $statusCards = [
            [
                'key' => 'total',
                'label' => 'Total animals as at period end',
                'value' => $totalAsAt,
                'subtitle' => 'All animal records captured up to ' . $toDate->format('d M Y'),
                'color' => '#0f766e',
                'icon' => 'heroicon-o-cube-transparent',
                'decision' => 'Use this as the baseline herd size for board-level livestock movement review.',
            ],
            [
                'key' => 'active',
                'label' => 'Active / Current',
                'value' => $active,
                'subtitle' => $activeRate . '% of total records',
                'color' => '#16a34a',
                'icon' => 'heroicon-o-check-badge',
                'decision' => 'Represents the productive herd that should drive feeding, health and sales planning.',
            ],
            [
                'key' => 'dead',
                'label' => 'Dead / Mortality',
                'value' => $dead,
                'subtitle' => $mortalityRate . '% mortality indicator',
                'color' => '#dc2626',
                'icon' => 'heroicon-o-exclamation-triangle',
                'decision' => 'High movement here should trigger vet review, mortality notes and cost impact analysis.',
            ],
            [
                'key' => 'culled',
                'label' => 'Culled',
                'value' => $culled,
                'subtitle' => 'Removed from productive herd',
                'color' => '#7c2d12',
                'icon' => 'heroicon-o-no-symbol',
                'decision' => 'Useful for identifying quality control, breeding performance and disposal decisions.',
            ],
            [
                'key' => 'sold',
                'label' => 'Sold',
                'value' => $sold,
                'subtitle' => $saleRate . '% sales/disposal indicator',
                'color' => '#d97706',
                'icon' => 'heroicon-o-banknotes',
                'decision' => 'Connect this to sales value, receivables and gross margin per animal.',
            ],
            [
                'key' => 'archived',
                'label' => 'Archived',
                'value' => $archived,
                'subtitle' => 'Records moved aside for control',
                'color' => '#2563eb',
                'icon' => 'heroicon-o-archive-box',
                'decision' => 'Archived records should be reviewed before board reports to avoid misleading herd counts.',
            ],
            [
                'key' => 'breeders',
                'label' => 'Breeding pool',
                'value' => $breeders,
                'subtitle' => 'Animals marked as breeders',
                'color' => '#059669',
                'icon' => 'heroicon-o-sparkles',
                'decision' => 'Use this number for conception planning, sire allocation and breeding cost review.',
            ],
            [
                'key' => 'new',
                'label' => 'Added in selected period',
                'value' => $newInPeriod,
                'subtitle' => $fromDate->format('d M Y') . ' to ' . $toDate->format('d M Y'),
                'color' => '#7c3aed',
                'icon' => 'heroicon-o-plus-circle',
                'decision' => 'Shows herd intake during the reporting window for growth and data-entry control.',
            ],
        ];

        if ($unknown > 0) {
            $statusCards[] = [
                'key' => 'unknown',
                'label' => 'Unknown / Unclassified',
                'value' => $unknown,
                'subtitle' => 'Needs data cleanup',
                'color' => '#64748b',
                'icon' => 'heroicon-o-question-mark-circle',
                'decision' => 'Clean these records before final management reports because they weaken decision accuracy.',
            ];
        }

        $statusDistribution = collect($statusCards)
            ->filter(fn ($card) => ! in_array($card['key'], ['new'], true))
            ->map(function ($card) use ($totalAsAt) {
                $card['percentage'] = $totalAsAt > 0 ? round(($card['value'] / $totalAsAt) * 100, 2) : 0;

                return $card;
            })
            ->values()
            ->all();

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'periodLabel' => $fromDate->format('d M Y') . ' to ' . $toDate->format('d M Y'),
            'asAtLabel' => $toDate->format('d M Y'),
            'generatedAt' => now('Africa/Nairobi'),
            'totalAsAt' => $totalAsAt,
            'newInPeriod' => $newInPeriod,
            'productivePool' => $productivePool,
            'active' => $active,
            'dead' => $dead,
            'culled' => $culled,
            'sold' => $sold,
            'archived' => $archived,
            'breeders' => $breeders,
            'unknown' => $unknown,
            'activeRate' => $activeRate,
            'mortalityRate' => $mortalityRate,
            'saleRate' => $saleRate,
            'statusCards' => $statusCards,
            'statusDistribution' => $statusDistribution,
            'dailyTrend' => $this->dailyTrend($fromDate, $toDate, $createdColumn),
            'breedDistribution' => $this->breedDistribution($asAtQuery, $breedColumn),
            'genderDistribution' => $this->genderDistribution($asAtQuery, $genderColumn),
            'recentMovementSummary' => $this->recentMovementSummary($fromDate, $toDate),
            'tableColumns' => compact('statusColumn', 'createdColumn', 'tagColumn', 'breedColumn', 'genderColumn', 'archivedColumn', 'breederColumn'),
            'note' => null,
        ];
    }

    protected function normalisePeriod(?string $from, ?string $to): array
    {
        $toDate = filled($to) ? Carbon::parse($to, 'Africa/Nairobi') : now('Africa/Nairobi');
        $fromDate = filled($from) ? Carbon::parse($from, 'Africa/Nairobi') : $toDate->copy()->startOfMonth();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate->startOfDay(), $toDate->endOfDay()];
    }

    protected function emptyReport(Carbon $fromDate, Carbon $toDate, string $note): array
    {
        return [
            'from' => $fromDate,
            'to' => $toDate,
            'periodLabel' => $fromDate->format('d M Y') . ' to ' . $toDate->format('d M Y'),
            'asAtLabel' => $toDate->format('d M Y'),
            'generatedAt' => now('Africa/Nairobi'),
            'totalAsAt' => 0,
            'newInPeriod' => 0,
            'productivePool' => 0,
            'active' => 0,
            'dead' => 0,
            'culled' => 0,
            'sold' => 0,
            'archived' => 0,
            'breeders' => 0,
            'unknown' => 0,
            'activeRate' => 0,
            'mortalityRate' => 0,
            'saleRate' => 0,
            'statusCards' => [],
            'statusDistribution' => [],
            'dailyTrend' => [],
            'breedDistribution' => [],
            'genderDistribution' => [],
            'recentMovementSummary' => [],
            'tableColumns' => [],
            'note' => $note,
        ];
    }

    protected function animalColumns(): array
    {
        return [
            'status' => $this->firstExistingColumn('animals', ['status', 'Status', 'animal_status']),
            'created_at' => $this->firstExistingColumn('animals', ['created_at', 'CreatedAt', 'date_added', 'DateAdded', 'registration_date']),
            'tag' => $this->firstExistingColumn('animals', ['tag_name', 'TagName', 'tag', 'animal_tag', 'name', 'AnimalName']),
            'breed' => $this->firstExistingColumn('animals', ['breed_name', 'breed', 'BreedName', 'BreedID', 'breed_id']),
            'gender' => $this->firstExistingColumn('animals', ['gender', 'sex', 'Gender', 'Sex']),
            'archived' => $this->firstExistingColumn('animals', ['is_archived', 'archived', 'IsArchived']),
            'breeder' => $this->firstExistingColumn('animals', ['is_breeder', 'breeder', 'IsBreeder']),
        ];
    }

    protected function firstExistingColumn(string $table, array $candidates): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function normaliseStatus(string $status): string
    {
        return Str::of($status)
            ->lower()
            ->replace(['_', '-'], ' ')
            ->squish()
            ->toString();
    }

    protected function sumStatuses(array $counts, array $keys): int
    {
        $total = 0;

        foreach ($counts as $status => $count) {
            foreach ($keys as $key) {
                if ($status === $this->normaliseStatus($key)) {
                    $total += (int) $count;
                }
            }
        }

        return $total;
    }

    protected function dailyTrend(Carbon $fromDate, Carbon $toDate, ?string $createdColumn): array
    {
        if (! $createdColumn || ! Schema::hasTable('animals')) {
            return [];
        }

        $maxDays = 45;
        $days = min($fromDate->diffInDays($toDate) + 1, $maxDays);
        $trendStart = $toDate->copy()->subDays($days - 1)->startOfDay();

        $rows = DB::table('animals')
            ->selectRaw('DATE(' . $createdColumn . ') as day, COUNT(*) as total')
            ->whereBetween($createdColumn, [$trendStart, $toDate])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->all();

        $period = CarbonPeriod::create($trendStart, '1 day', $toDate->copy()->startOfDay());
        $max = max(1, (int) (count($rows) ? max($rows) : 1));
        $data = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $value = (int) ($rows[$key] ?? 0);

            $data[] = [
                'date' => $key,
                'label' => $date->format('d M'),
                'value' => $value,
                'height' => max(5, (int) round(($value / $max) * 100)),
            ];
        }

        return $data;
    }

    protected function breedDistribution($asAtQuery, ?string $breedColumn): array
    {
        if (! $breedColumn) {
            return [];
        }

        return (clone $asAtQuery)
            ->select($breedColumn . ' as label', DB::raw('COUNT(*) as total'))
            ->groupBy($breedColumn)
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) ($row->label ?: 'Unspecified'),
                'value' => (int) $row->total,
            ])
            ->all();
    }

    protected function genderDistribution($asAtQuery, ?string $genderColumn): array
    {
        if (! $genderColumn) {
            return [];
        }

        return (clone $asAtQuery)
            ->select($genderColumn . ' as label', DB::raw('COUNT(*) as total'))
            ->groupBy($genderColumn)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) ($row->label ?: 'Unspecified'),
                'value' => (int) $row->total,
            ])
            ->all();
    }

    protected function recentMovementSummary(Carbon $fromDate, Carbon $toDate): array
    {
        $summary = [];

        if (Schema::hasTable('animal_events')) {
            $eventDateColumn = $this->firstExistingColumn('animal_events', ['event_date', 'date', 'created_at', 'EventDate']);
            $eventTypeColumn = $this->firstExistingColumn('animal_events', ['event_type', 'type', 'status', 'EventType']);

            if ($eventDateColumn && $eventTypeColumn) {
                $summary = DB::table('animal_events')
                    ->select($eventTypeColumn . ' as label', DB::raw('COUNT(*) as total'))
                    ->whereBetween($eventDateColumn, [$fromDate, $toDate])
                    ->groupBy($eventTypeColumn)
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get()
                    ->map(fn ($row) => [
                        'label' => (string) ($row->label ?: 'Unspecified Event'),
                        'value' => (int) $row->total,
                    ])
                    ->all();
            }
        }

        return $summary;
    }
}
