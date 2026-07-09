<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class AnimalProfilePdfController extends Controller
{
    public function __invoke(Animal $animal)
    {
        abort_unless(auth()->user()?->can('view animals') ?? false, 403);

        $animal->load([
            'breed',
            'purityBreed',
            'location',
            'latestWeight',
            'sire.breed',
            'dam.breed',
            'sire.sire.breed',
            'sire.dam.breed',
            'dam.sire.breed',
            'dam.dam.breed',
            'healthAdministrations' => fn ($query) => $query
                ->with('product')
                ->orderByDesc('administered_at')
                ->orderByDesc('id'),
            'clinicalCases' => fn ($query) => $query
                ->orderByDesc('case_date')
                ->orderByDesc('id'),
            'treatmentRecords' => fn ($query) => $query
                ->with('clinicalCase')
                ->orderByDesc('given_at')
                ->orderByDesc('id'),
            'labRequests' => fn ($query) => $query
                ->with(['clinicalCase', 'veterinaryClinic'])
                ->orderByDesc('requested_at')
                ->orderByDesc('id'),
        ]);

        $generatedBy = auth()->user();
        $generatedByRole = $generatedBy?->getRoleNames()?->first() ?? 'User';
        $generatedAt = now('Africa/Nairobi');
        $profileUrl = url('/admin/animals/animals/' . $animal->getKey() . '/profile');

        $qrImage = null;

        try {
            $qrImage = 'data:image/png;base64,' . base64_encode(
                QrCode::format('png')
                    ->size(112)
                    ->margin(1)
                    ->generate($profileUrl)
            );
        } catch (Throwable) {
            $qrImage = null;
        }

        $pdf = Pdf::loadView('pdf.animal-profile', [
            'animal' => $animal,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
            'generatedAt' => $generatedAt,
            'profileUrl' => $profileUrl,
            'qrImage' => $qrImage,
            'farmName' => setting('farm.name', 'Penzi Farm Limited'),
            'farmTagline' => setting(
                'farm.tagline',
                'Nurturing Quality, Inspiring Global Standards'
            ),
            'farmPhone' => setting('farm.phone', '+254 757 046 726'),
            'farmEmail' => setting('farm.email', 'jambo@penzifarm.com'),
            'farmCounty' => setting('farm.county', 'Molo - Nakuru County'),
            'farmLegalName' => setting(
                'farm.legal_name',
                setting('farm.name', 'Penzi Farm Limited')
            ),
            'primaryColor' => trim(setting('theme.primary', '#14532d')),
            'secondaryColor' => trim(setting('theme.secondary', '#166534')),
            'accentColor' => trim(setting('theme.accent', '#b7791f')),
            'logoPath' => setting('branding.logo_light', null),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 96,
                'defaultFont' => 'Courier',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'chroot' => base_path(),
            ]);

        return $pdf->stream(
            strtoupper($animal->tag_number) . '-pedigree-profile.pdf'
        );
    }
}
