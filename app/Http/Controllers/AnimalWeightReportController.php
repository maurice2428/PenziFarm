<?php

namespace App\Http\Controllers;

use App\Models\AnimalWeight;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AnimalWeightReportController extends Controller
{
    public function bulkReport(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404, 'No weight records selected.');

        $weights = AnimalWeight::query()
            ->withTrashed()
            ->with(['animal.breed', 'animal.location', 'recorder'])
            ->whereIn('id', $ids)
            ->orderBy('recorded_at', 'desc')
            ->get();

        abort_if($weights->isEmpty(), 404, 'Selected weight records were not found.');

        $generatedBy = auth()->user();

        $generatedByRole = 'User';

        if ($generatedBy && method_exists($generatedBy, 'roles')) {
            $generatedByRole = $generatedBy->roles?->pluck('name')->join(', ') ?: 'User';
        }

        $qrImage = null;

        $pdf = Pdf::loadView('pdf.animal-weight-bulk-report', [
            'weights' => $weights,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
            'qrImage' => $qrImage,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('animal-weight-bulk-report-' . now()->format('Ymd-His') . '.pdf');
    }
}
