<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
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

        /*
         * Keep the QR destination aligned with the Filament resource route.
         * The profile route itself is registered from AnimalResource::getPages().
         */
        $profileUrl = url(
            '/admin/animals/animals/'
            . $animal->getKey()
            . '/profile'
        );

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
            'accentColor' => trim(setting('theme.accent', '#d97706')),
            'dangerColor' => trim(setting('theme.danger', '#b91c1c')),
            'logoBase64' => $this->imageDataUri(
                setting('branding.logo_light')
            ),
            'profileUrl' => $profileUrl,
            'qrImage' => $qrImage,

            /*
             * A deliberately fixed two-page certificate:
             * recent operational records are limited so the layout stays compact.
             */
            'healthAdministrations' => $animal->healthAdministrations->take(10),
            'healthAdministrationCount' => $animal->healthAdministrations->count(),
            'clinicalCases' => $animal->clinicalCases->take(5),
            'clinicalCaseCount' => $animal->clinicalCases->count(),
            'treatments' => $animal->treatmentRecords->take(5),
            'treatmentCount' => $animal->treatmentRecords->count(),
            'labRequests' => $animal->labRequests->take(5),
            'labRequestCount' => $animal->labRequests->count(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 96,
                'defaultFont' => 'Courier',
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'chroot' => base_path(),
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
            ]);

        return $pdf->stream(
            Str::upper($animal->tag_number) . '-pedigree-profile.pdf'
        );
    }

    private function imageDataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (Str::startsWith($path, 'data:')) {
            return $path;
        }

        $cleanPath = preg_replace(
            '#^storage/#',
            '',
            ltrim($path, '/')
        );

        $paths = [
            storage_path('app/public/' . $cleanPath),
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
        ];

        foreach ($paths as $fullPath) {
            if (! is_file($fullPath)) {
                continue;
            }

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode(
                file_get_contents($fullPath)
            );
        }

        return null;
    }
}
