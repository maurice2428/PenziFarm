<?php

namespace App\Http\Controllers;

use App\Models\AnimalWeight;
use Barryvdh\DomPDF\Facade\Pdf;
//use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class BreedWeightReportController extends Controller
{
    public function download(): StreamedResponse
    {
        $user = auth()->user();

        $generatedByRole = $user?->getRoleNames()?->first() ?? 'User';

        $latestWeightIds = AnimalWeight::query()
            ->selectRaw('MAX(id)')
            ->whereNull('deleted_at')
            ->groupBy('animal_id');

        $breedAverages = AnimalWeight::query()
            ->join('animals', 'animal_weights.animal_id', '=', 'animals.id')
            ->join('breeds', 'animals.breed_id', '=', 'breeds.id')
            ->whereNull('animal_weights.deleted_at')
            ->whereIn('animal_weights.id', $latestWeightIds)
            ->select([
                'breeds.id as breed_id',
                'breeds.breed_name',
                'breeds.parent_category',
                DB::raw('COUNT(DISTINCT animals.id) as animals_count'),
                DB::raw('ROUND(AVG(animal_weights.weight_kg), 2) as avg_weight'),
                DB::raw('ROUND(MAX(animal_weights.weight_kg), 2) as max_weight'),
                DB::raw('ROUND(MIN(animal_weights.weight_kg), 2) as min_weight'),
            ])
            ->groupBy(
                'breeds.id',
                'breeds.breed_name',
                'breeds.parent_category'
            )
            ->orderByDesc('avg_weight')
            ->get();

        $pdf = Pdf::loadView('pdf.breed-weight-report', [
            'breedAverages' => $breedAverages,
            'generatedBy' => $user,
            'generatedByRole' => $generatedByRole,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'breed-weight-report-' . now('Africa/Nairobi')->format('Ymd_His') . '.pdf'
        );
    }
}
