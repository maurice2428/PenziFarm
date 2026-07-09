<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class AnimalProfilePdfController extends Controller
{
    public function __invoke(Animal $animal): Response
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
        $farmName = setting('farm.name', 'Penzi Farm Limited');
        $profileUrl = url('/admin/animals/animals/' . $animal->getKey() . '/profile');

        $verificationText = implode(' | ', [
            $farmName,
            'Animal Profile Certificate',
            'Tag: ' . $animal->tag_number,
            'Breed: ' . ($animal->breed?->breed_name ?? 'Not recorded'),
            'Generated: ' . now('Africa/Nairobi')->format('Y-m-d H:i:s') . ' EAT',
            'Profile: ' . $profileUrl,
        ]);

        $qrImage = null;

        try {
            $qrImage = 'data:image/png;base64,' . base64_encode(
                QrCode::format('png')
                    ->size(135)
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
            'generatedAt' => now('Africa/Nairobi'),
            'farmName' => $farmName,
            'farmTagline' => setting(
                'farm.tagline',
                'Nurturing Quality, Inspiring Global Standards'
            ),
            'farmPhone' => setting('farm.phone', '+254 757 046 726'),
            'farmEmail' => setting('farm.email', 'jambo@penzifarm.com'),
            'farmCounty' => setting('farm.county', 'Molo - Nakuru County'),
            'farmLegalName' => setting('farm.legal_name', $farmName),
            'logoBase64' => $this->imageDataUri(
                setting('branding.logo_light')
            ),
            'primaryColor' => trim(setting('theme.primary', '#14532d')),
            'secondaryColor' => trim(setting('theme.secondary', '#166534')),
            'accentColor' => trim(setting('theme.accent', '#d97706')),
            'dangerColor' => trim(setting('theme.danger', '#b91c1c')),
            'successColor' => trim(setting('theme.success', '#16a34a')),
            'profileUrl' => $profileUrl,
            'verificationText' => $verificationText,
            'qrImage' => $qrImage,
            // Fixed-page certificate: concise, most recent operational records.
            'healthAdministrations' => $animal->healthAdministrations->take(8),
            'healthAdministrationCount' => $animal->healthAdministrations->count(),
            'clinicalCases' => $animal->clinicalCases->take(3),
            'clinicalCaseCount' => $animal->clinicalCases->count(),
            'treatments' => $animal->treatmentRecords->take(4),
            'treatmentCount' => $animal->treatmentRecords->count(),
            'labRequests' => $animal->labRequests->take(4),
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
                'isPhpEnabled' => true,
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

        $cleanPath = ltrim($path, '/');
        $cleanPath = preg_replace('#^storage/#', '', $cleanPath);

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
