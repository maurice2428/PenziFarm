<?php

namespace App\Http\Controllers\Breeding;

use App\Http\Controllers\Controller;
use App\Models\BreedingBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BreedingBatchReportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(
            auth()->user()?->can('print breeding batches')
            || auth()->user()?->can('view breeding batches')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator'),
            403
        );

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404, 'No breeding batches selected.');

        $batches = BreedingBatch::query()
            ->with([
                'male.breed',
                'maleBreed',
                'records.female.breed',
                'records.male.breed',
                'records.femaleBreed',
                'records.maleBreed',
            ])
            ->whereIn('id', $ids)
            ->orderBy('mating_date')
            ->orderBy('id')
            ->get();

        abort_if($batches->isEmpty(), 404, 'Selected breeding batches were not found.');

        $records = $batches->flatMap(fn (BreedingBatch $batch) => $batch->records);

        $dueDates = $records
            ->pluck('expected_due_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sort()
            ->values();

        $speciesBreakdown = $records
            ->groupBy(fn ($record) => $record->species ?: 'Unknown')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $breedBreakdown = $records
            ->groupBy(function ($record) {
                return $record->femaleBreed?->breed_name
                    ?? $record->female?->breed?->breed_name
                    ?? 'Unknown Breed';
            })
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $pregnancyBreakdown = $records
            ->groupBy(fn ($record) => $record->pregnancy_status ?: 'pending')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $inbreedingBreakdown = $records
            ->groupBy(fn ($record) => $record->inbreeding_status ?: 'clear')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $insights = [
            'total_batches' => $batches->count(),
            'total_females' => $records->count(),
            'cross_breeding_records' => $records->where('is_cross_breed', true)->count(),
            'natural_batches' => $batches->where('breeding_type', 'natural')->count(),
            'ai_batches' => $batches->where('breeding_type', 'artificial_insemination')->count(),
            'embryo_batches' => $batches->where('breeding_type', 'embryo_transfer')->count(),
            'due_from' => $dueDates->first()?->format('d M Y'),
            'due_to' => $dueDates->last()?->format('d M Y'),
            'next_due' => $dueDates->first()?->format('d M Y'),
            'species_breakdown' => $speciesBreakdown,
            'breed_breakdown' => $breedBreakdown,
            'pregnancy_breakdown' => $pregnancyBreakdown,
            'inbreeding_breakdown' => $inbreedingBreakdown,
            'clear_inbreeding' => (int) ($inbreedingBreakdown['clear'] ?? 0),
            'warning_inbreeding' => (int) ($inbreedingBreakdown['warning'] ?? 0),
            'blocked_inbreeding' => (int) ($inbreedingBreakdown['blocked'] ?? 0),
        ];

        $reportNumber = 'BRD-REPORT-' . now('Africa/Nairobi')->format('Ymd-His');

        $pdf = Pdf::loadView('pdfs.breeding.selected-batches-report', [
            'batches' => $batches,
            'records' => $records,
            'insights' => $insights,
            'reportNumber' => $reportNumber,
            'generatedBy' => auth()->user(),
            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
        ])
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return $pdf->stream($reportNumber . '.pdf');
    }
}
