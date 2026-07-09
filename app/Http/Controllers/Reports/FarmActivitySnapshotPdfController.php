<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FarmActivitySnapshotPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $from = $this->cleanDate($request->query('from'), now('Africa/Nairobi')->startOfMonth()->toDateString());
        $to = $this->cleanDate($request->query('to'), now('Africa/Nairobi')->toDateString());

        if ($from && $to && Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        $generatedBy = $request->user();
        $generatedByRole = method_exists($generatedBy, 'getRoleNames')
            ? ($generatedBy?->getRoleNames()?->first() ?: 'User')
            : 'User';

        $snapshot = $this->snapshot($from, $to);

        $viewData = [
            'snapshot' => $snapshot,
            'from' => $from,
            'to' => $to,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
        ];

        $filename = 'livestock-activity-snapshot-' . Carbon::parse($to)->format('Y-m-d') . '.pdf';

        if (class_exists(Pdf::class)) {
            return Pdf::loadView('reports.farm-activity.livestock-snapshot-pdf', $viewData)
                ->setPaper('a4', 'landscape')
                ->download($filename);
        }

        return view('reports.farm-activity.livestock-snapshot-pdf', $viewData);
    }

    protected function snapshot(string $from, string $to): array
    {
        if (! Schema::hasTable('animals')) {
            return $this->emptySnapshot('The animals table was not found.', $from, $to);
        }

        $base = $this->animalBaseQuery($to);
        $total = (clone $base)->count();
        $statusRows = $this->groupRowsFromAnimalBase($base, ['status', 'animal_status', 'record_status'], 'Animal Status', 20);
        $statusTotals = $this->statusTotals($statusRows['rows']);
        $additions = $this->periodAdditions($from, $to);
        $additionTrend = $this->periodAdditionTrend($from, $to);
        $breedRows = $this->breedSnapshot($base);
        $genderRows = $this->groupRowsFromAnimalBase($base, ['sex', 'gender'], 'Sex / Gender', 10);
        $stageRows = $this->groupRowsFromAnimalBase($base, ['animal_stage', 'stage', 'production_stage', 'life_stage'], 'Stage', 10);
        $locationRows = $this->groupRowsFromAnimalBase($base, ['location', 'farm_location', 'pen', 'paddock', 'unit'], 'Location / Unit', 10);
        $events = $this->animalEvents($from, $to);

        $active = $statusTotals['active'];
        $dead = $statusTotals['dead'];
        $culled = $statusTotals['culled'];
        $sold = $statusTotals['sold'];
        $archived = $statusTotals['archived'];
        $exits = $dead + $culled + $sold + $archived;

        $activeRate = $this->percentage($active, $total);
        $exitRate = $this->percentage($exits, $total);
        $mortalityRate = $this->percentage($dead, $total);
        $additionRate = $this->percentage($additions, max($total, 1));

        return [
            'generated_at' => now('Africa/Nairobi'),
            'from' => $from,
            'to' => $to,
            'period_label' => Carbon::parse($from)->format('d M Y') . ' to ' . Carbon::parse($to)->format('d M Y'),
            'as_at_label' => Carbon::parse($to)->format('d M Y'),
            'total' => (int) $total,
            'status_rows' => $statusRows['rows'],
            'status_column' => $statusRows['column'],
            'status_totals' => $statusTotals,
            'active_rate' => $activeRate,
            'exit_rate' => $exitRate,
            'mortality_rate' => $mortalityRate,
            'addition_rate' => $additionRate,
            'additions' => (int) $additions,
            'addition_trend' => $additionTrend,
            'breed_rows' => $breedRows,
            'gender_rows' => $genderRows,
            'stage_rows' => $stageRows,
            'location_rows' => $locationRows,
            'events' => $events,
            'insights' => $this->buildInsights((int) $total, $statusTotals, (int) $additions, $events, $breedRows, $additionRate),
            'empty_reason' => null,
        ];
    }

    protected function emptySnapshot(?string $reason, string $from, string $to): array
    {
        return [
            'generated_at' => now('Africa/Nairobi'),
            'from' => $from,
            'to' => $to,
            'period_label' => Carbon::parse($from)->format('d M Y') . ' to ' . Carbon::parse($to)->format('d M Y'),
            'as_at_label' => Carbon::parse($to)->format('d M Y'),
            'total' => 0,
            'status_rows' => [],
            'status_column' => null,
            'status_totals' => ['active' => 0, 'dead' => 0, 'culled' => 0, 'sold' => 0, 'archived' => 0, 'unknown' => 0],
            'active_rate' => 0,
            'exit_rate' => 0,
            'mortality_rate' => 0,
            'addition_rate' => 0,
            'additions' => 0,
            'addition_trend' => [],
            'breed_rows' => [],
            'gender_rows' => ['title' => 'Sex / Gender', 'column' => null, 'rows' => []],
            'stage_rows' => ['title' => 'Stage', 'column' => null, 'rows' => []],
            'location_rows' => ['title' => 'Location / Unit', 'column' => null, 'rows' => []],
            'events' => ['total_events' => 0, 'rows' => []],
            'insights' => [$reason ?: 'No animal data is available for this report.'],
            'empty_reason' => $reason,
        ];
    }

    protected function cleanDate(?string $date, string $fallback): string
    {
        try {
            return Carbon::parse($date ?: $fallback)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::parse($fallback)->toDateString();
        }
    }

    protected function animalBaseQuery(?string $to = null)
    {
        $query = DB::table('animals');

        if ($to && Schema::hasColumn('animals', 'created_at')) {
            $query->whereDate('animals.created_at', '<=', $to);
        }

        return $query;
    }

    protected function periodAdditions(?string $from, ?string $to): int
    {
        if (! Schema::hasColumn('animals', 'created_at')) {
            return 0;
        }

        $query = DB::table('animals');

        if ($from) {
            $query->whereDate('animals.created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('animals.created_at', '<=', $to);
        }

        return (int) $query->count();
    }

    protected function periodAdditionTrend(?string $from, ?string $to): array
    {
        if (! Schema::hasColumn('animals', 'created_at')) {
            return [];
        }

        $query = DB::table('animals')
            ->selectRaw('DATE(animals.created_at) as activity_date')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw('DATE(animals.created_at)')
            ->orderBy('activity_date');

        if ($from) {
            $query->whereDate('animals.created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('animals.created_at', '<=', $to);
        }

        return $query->limit(31)->get()->map(fn ($row) => [
            'date' => Carbon::parse($row->activity_date)->format('d M'),
            'full_date' => $row->activity_date,
            'total' => (int) $row->total,
        ])->all();
    }

    protected function groupRowsFromAnimalBase($baseQuery, array $possibleColumns, string $title, int $limit = 10): array
    {
        $column = collect($possibleColumns)->first(fn ($candidate) => Schema::hasColumn('animals', $candidate));

        if (! $column) {
            return ['title' => $title, 'column' => null, 'rows' => []];
        }

        $qualifiedColumn = 'animals.`' . str_replace('`', '``', $column) . '`';

        $rows = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(CAST({$qualifiedColumn} AS CHAR), ''), 'Unspecified') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw($qualifiedColumn)
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => Str::title((string) $row->label),
                'raw_label' => (string) $row->label,
                'total' => (int) $row->total,
            ])->all();

        return ['title' => $title, 'column' => $column, 'rows' => $rows];
    }

    protected function breedSnapshot($baseQuery): array
    {
        if (! Schema::hasTable('breeds') || ! Schema::hasColumn('animals', 'breed_id')) {
            return [];
        }

        $breedName = Schema::hasColumn('breeds', 'breed_name')
            ? 'breeds.breed_name'
            : (Schema::hasColumn('breeds', 'name') ? 'breeds.name' : null);

        if (! $breedName) {
            return [];
        }

        $parentCategory = Schema::hasColumn('breeds', 'parent_category') ? 'breeds.parent_category' : $breedName;

        return (clone $baseQuery)
            ->leftJoin('breeds', 'breeds.id', '=', 'animals.breed_id')
            ->selectRaw("COALESCE({$breedName}, 'Unspecified Breed') as breed_name")
            ->selectRaw("COALESCE({$parentCategory}, 'Livestock') as parent_category")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("{$breedName}, {$parentCategory}")
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'breed_name' => (string) $row->breed_name,
                'parent_category' => (string) $row->parent_category,
                'total' => (int) $row->total,
            ])->all();
    }

    protected function animalEvents(?string $from, ?string $to): array
    {
        if (! Schema::hasTable('animal_events')) {
            return ['total_events' => 0, 'rows' => []];
        }

        $dateColumn = collect(['event_date', 'date', 'recorded_at', 'created_at'])
            ->first(fn ($candidate) => Schema::hasColumn('animal_events', $candidate));

        $base = DB::table('animal_events');

        if ($dateColumn) {
            $qualifiedDateColumn = 'animal_events.`' . str_replace('`', '``', $dateColumn) . '`';

            if ($from) {
                $base->whereDate(DB::raw($qualifiedDateColumn), '>=', $from);
            }

            if ($to) {
                $base->whereDate(DB::raw($qualifiedDateColumn), '<=', $to);
            }
        }

        $total = (clone $base)->count();
        $typeColumn = collect(['type', 'event_type', 'activity_type', 'category', 'status'])
            ->first(fn ($candidate) => Schema::hasColumn('animal_events', $candidate));

        if (! $typeColumn) {
            return ['total_events' => (int) $total, 'rows' => []];
        }

        $qualifiedTypeColumn = 'animal_events.`' . str_replace('`', '``', $typeColumn) . '`';

        $rows = (clone $base)
            ->selectRaw("COALESCE(NULLIF(CAST({$qualifiedTypeColumn} AS CHAR), ''), 'Unspecified Event') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw($qualifiedTypeColumn)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => Str::title((string) $row->label),
                'total' => (int) $row->total,
            ])->all();

        return ['total_events' => (int) $total, 'rows' => $rows];
    }

    protected function statusTotals(array $rows): array
    {
        $totals = ['active' => 0, 'dead' => 0, 'culled' => 0, 'sold' => 0, 'archived' => 0, 'unknown' => 0];

        foreach ($rows as $row) {
            $label = Str::lower((string) ($row['raw_label'] ?? $row['label'] ?? ''));
            $count = (int) ($row['total'] ?? 0);

            if (str_contains($label, 'cull')) {
                $totals['culled'] += $count;
            } elseif (str_contains($label, 'dead') || str_contains($label, 'mortality') || str_contains($label, 'deceased')) {
                $totals['dead'] += $count;
            } elseif (str_contains($label, 'sold')) {
                $totals['sold'] += $count;
            } elseif (str_contains($label, 'archiv') || str_contains($label, 'inactive')) {
                $totals['archived'] += $count;
            } elseif (str_contains($label, 'active') || str_contains($label, 'current') || str_contains($label, 'alive')) {
                $totals['active'] += $count;
            } else {
                $totals['unknown'] += $count;
            }
        }

        return $totals;
    }

    protected function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }

    protected function buildInsights(int $total, array $statusTotals, int $additions, array $events, array $breedRows, float $additionRate): array
    {
        if ($total <= 0) {
            return ['No animal records were found for the selected as-at date. Confirm that animal data has been uploaded and that the selected period is correct.'];
        }

        $active = $statusTotals['active'];
        $dead = $statusTotals['dead'];
        $culled = $statusTotals['culled'];
        $sold = $statusTotals['sold'];
        $archived = $statusTotals['archived'];
        $unknown = $statusTotals['unknown'];
        $exits = $dead + $culled + $sold + $archived;

        $insights = [];
        $insights[] = 'The report is an as-at snapshot: it counts animal records created on or before the selected report date and groups them by their current recorded status.';
        $insights[] = "Active/current animals represent {$this->percentage($active, $total)}% of the visible herd records, while recorded exits represent {$this->percentage($exits, $total)}%.";

        if ($dead > 0) {
            $insights[] = "Mortality records account for {$this->percentage($dead, $total)}% of the herd snapshot. Review recent health treatments, vaccination coverage and animal event notes for supporting causes.";
        }

        if ($culled > 0) {
            $insights[] = "Culled animals are present in the period snapshot. This should be reviewed against productivity, health history and breeding suitability to confirm culling decisions were economically justified.";
        }

        if ($sold > 0) {
            $insights[] = "Sold animals are included as commercial exits. Reconcile this count with sales invoices, animal payment records and accounting postings.";
        }

        if ($unknown > 0) {
            $insights[] = "{$unknown} animal record(s) have an unspecified or non-standard status. Cleaning these records will improve reporting accuracy and dashboard decisions.";
        }

        if ($additions > 0) {
            $insights[] = "{$additions} new animal record(s) were added during the selected period, equivalent to {$additionRate}% of the as-at herd size. This helps show intake or birth recording activity.";
        }

        if (($events['total_events'] ?? 0) > 0) {
            $insights[] = "{$events['total_events']} animal event(s) were captured in the period. The event mix should be reviewed against health, breeding, weighing and movement workflows.";
        }

        if (! empty($breedRows)) {
            $topBreed = $breedRows[0];
            $insights[] = "The leading breed/category is {$topBreed['breed_name']} with {$topBreed['total']} record(s). This indicates where most herd concentration currently sits.";
        }

        return $insights;
    }
}
