<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Services\BreedingRiskAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class BreedingPerformanceReportController extends Controller
{
    public function __invoke(
        Animal $animal,
        BreedingRiskAnalyticsService $service
    ): Response {
        abort_unless(
            auth()->user()?->can('view breeding risk dashboard')
                || auth()->user()?->can('view progeny analytics')
                || auth()->user()?->can('view breeding outcomes')
                || auth()->user()?->hasAnyRole([
                    'Administrator',
                    'Admin',
                    'Manager',
                    'Veterinary Officer',
                ]),
            403
        );

        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');
        set_time_limit(300);

        $snapshot = $service->animalSnapshot($animal);
        $user = auth()->user();

        $pdf = Pdf::loadView(
            'pdf.breeding-performance-history',
            [
                'animal' => $snapshot['animal'],
                'metrics' => $snapshot['metrics'],
                'history' => $snapshot['history'],
                'riskFlags' => $snapshot['risk_flags'],
                'generatedBy' => $user,
                'generatedByRole' =>
                    $user?->getRoleNames()?->first() ?? 'User',
            ]
        )
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'Courier',
            ]);

        $filename = 'breeding-performance-'
            . str($animal->tag_number ?: $animal->id)
                ->slug('-')
            . '-'
            . now('Africa/Nairobi')->format('Ymd_His')
            . '.pdf';

        return $pdf->stream($filename);
    }
}
