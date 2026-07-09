<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Services\ProgenyAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProgenyReportController extends Controller
{
    public function __invoke(
        Request $request,
        Animal $animal,
        ProgenyAnalyticsService $analytics
    ) {
        abort_unless(
            auth()->user()?->can('print progeny reports')
            || auth()->user()?->hasAnyRole(['Administrator', 'Admin', 'Manager', 'Veterinary Officer']),
            403
        );

        $generations = max(1, min(5, $request->integer('generations', 3)));
        $mode = $request->string('mode')->toString() === 'ancestors'
            ? 'ancestors'
            : 'descendants';

        $animal->load([
            'breed:id,breed_name',
            'location:id,name',
            'sire.breed:id,breed_name',
            'dam.breed:id,breed_name',
        ]);

        $tree = $analytics->tree($animal, $generations, $mode);
        $metrics = $analytics->metrics($animal);
        $latestReview = $analytics->latestReview($animal);
        $generatedAt = now('Africa/Nairobi');
        $farmName = setting('farm.name', 'Penzi Farm');
        $generatedBy = auth()->user();
        $generatedByRole = $generatedBy?->getRoleNames()?->first() ?? 'User';

        $verificationText = implode(' | ', [
            $farmName . ' Progeny Report',
            'Animal: ' . $animal->tag_number,
            'Mode: ' . ucfirst($mode),
            'Generations: ' . $generations,
            'Generated: ' . $generatedAt->format('Y-m-d H:i:s') . ' EAT',
            'By: ' . ($generatedBy?->name ?? 'System'),
        ]);

        $qrImage = null;

        try {
            $qrImage = 'data:image/png;base64,' . base64_encode(
                QrCode::format('png')->size(120)->margin(1)->generate($verificationText)
            );
        } catch (\Throwable) {
            $qrImage = null;
        }

        $pdf = Pdf::loadView('pdf.progeny-report', [
            'animal' => $animal,
            'tree' => $tree,
            'metrics' => $metrics,
            'latestReview' => $latestReview,
            'generations' => $generations,
            'mode' => $mode,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
            'farmName' => $farmName,
            'verificationText' => $verificationText,
            'qrImage' => $qrImage,
        ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 96,
                'defaultFont' => 'Courier',
                'enable_php' => true,
            ]);

        $filename = sprintf(
            '%s-%s-%dg-%s.pdf',
            strtolower($mode),
            preg_replace('/[^A-Za-z0-9_-]/', '-', $animal->tag_number),
            $generations,
            $generatedAt->format('Ymd_His')
        );

        return $pdf->stream($filename);
    }
}
