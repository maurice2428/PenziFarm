<?php

namespace App\Services\Reports;

use App\Models\Settings\PaymentSetting;
use Illuminate\Support\Facades\Schema;

class ReportBrandingService
{
    public function accounting(): array
    {
        $paymentSettings = $this->paymentSettings();

        $farmName = $this->setting(
            'farm.name',
            $this->setting(
                'company.name',
                config('app.name', 'Farm Management System')
            )
        );

        $logoPath = $this->firstFilled([
            $this->setting('branding.logo_light'),
            $this->setting('branding.logo'),
            $this->setting('branding.logo_dark'),
            $this->setting('farm.logo'),
            $this->setting('company.logo'),
            data_get($paymentSettings, 'company_logo'),
            data_get($paymentSettings, 'logo'),
        ]);

        return [
            'farmName' => $farmName,
            'farmTagline' => $this->setting(
                'farm.tagline',
                'Nurturing Quality, Inspiring Global Standards'
            ),
            'farmPhone' => $this->setting('farm.phone', ''),
            'farmEmail' => $this->setting('farm.email', ''),
            'farmCounty' => $this->setting('farm.county', 'Kenya'),
            'farmAddress' => $this->setting(
                'farm.address',
                $this->setting('company.address', '')
            ),
            'farmWebsite' => $this->setting(
                'farm.website',
                $this->setting('company.website', '')
            ),
            'kraPin' => $this->setting(
                'farm.kra_pin',
                $this->setting('company.kra_pin', '')
            ),
            'currency' => data_get(
                $paymentSettings,
                'default_currency',
                $this->setting('accounting.currency', 'KES')
            ) ?: 'KES',
            'primaryColor' => $this->safeColor(
                $this->setting('theme.primary', '#14532d'),
                '#14532d'
            ),
            'secondaryColor' => $this->safeColor(
                $this->setting('theme.secondary', '#166534'),
                '#166534'
            ),
            'accentColor' => $this->safeColor(
                $this->setting('theme.accent', '#f59e0b'),
                '#f59e0b'
            ),
            'successColor' => $this->safeColor(
                $this->setting('theme.success', '#16a34a'),
                '#16a34a'
            ),
            'dangerColor' => $this->safeColor(
                $this->setting('theme.danger', '#dc2626'),
                '#dc2626'
            ),
            'logoBase64' => $this->imageBase64($logoPath)
                ?: $this->commonLogoBase64(),
            'signatureBase64' => $this->imageBase64(
                $this->firstFilled([
                    data_get(
                        $paymentSettings,
                        'authorized_signature_image'
                    ),
                    data_get(
                        $paymentSettings,
                        'invoice_signature_path'
                    ),
                    data_get($paymentSettings, 'signature_path'),
                    data_get(
                        $paymentSettings,
                        'authorized_signature_path'
                    ),
                    $this->setting('branding.signature'),
                    $this->setting('farm.signature'),
                ])
            ),
            'stampBase64' => $this->imageBase64(
                $this->firstFilled([
                    data_get(
                        $paymentSettings,
                        'payment_stamp_image'
                    ),
                    data_get(
                        $paymentSettings,
                        'invoice_stamp_path'
                    ),
                    data_get($paymentSettings, 'stamp_path'),
                    data_get(
                        $paymentSettings,
                        'official_stamp_path'
                    ),
                    $this->setting('branding.stamp'),
                    $this->setting('farm.stamp'),
                ])
            ),
            'authorizedName' => $this->setting(
                'farm.authorized_signatory_name',
                $this->setting(
                    'company.authorized_signatory_name',
                    'Authorised Signatory'
                )
            ),
            'authorizedTitle' => $this->setting(
                'farm.authorized_signatory_title',
                $this->setting(
                    'company.authorized_signatory_title',
                    'Finance / Management'
                )
            ),
            /*
             * Accounting reports must not inherit invoice notes such as
             * "Thank you" or sales-payment wording.
             */
            'footerNote' => $this->setting(
                'accounting.report_footer_note',
                'System-generated management accounting report.'
            ),
            'confidentialityNote' => $this->setting(
                'accounting.report_confidentiality_note',
                'Confidential management accounting report'
            ),
            'generatedAt' => now('Africa/Nairobi'),
        ];
    }

    public function imageBase64(mixed $path): ?string
    {
        $path = $this->normalizeImagePath($path);

        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        $urlPath = parse_url($path, PHP_URL_PATH);

        if (is_string($urlPath) && $urlPath !== '') {
            $path = $urlPath;
        }

        $cleanPath = preg_replace(
            '#^storage/#',
            '',
            ltrim($path, '/')
        );

        $possiblePaths = array_filter([
            storage_path('app/public/' . $cleanPath),
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
            is_file($path) ? $path : null,
        ]);

        foreach ($possiblePaths as $fullPath) {
            if (! is_file($fullPath) || ! is_readable($fullPath)) {
                continue;
            }

            $extension = strtolower(
                pathinfo($fullPath, PATHINFO_EXTENSION)
            );

            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default =>
                    mime_content_type($fullPath)
                    ?: 'image/png',
            };

            return 'data:' . $mime . ';base64,'
                . base64_encode(
                    file_get_contents($fullPath)
                );
        }

        return null;
    }

    private function commonLogoBase64(): ?string
    {
        foreach ([
            public_path('logo.png'),
            public_path('images/logo.png'),
            public_path('images/branding/logo.png'),
            public_path('images/penzi-logo.png'),
            storage_path('app/public/branding/logo.png'),
        ] as $candidate) {
            $encoded = $this->imageBase64($candidate);

            if ($encoded) {
                return $encoded;
            }
        }

        return null;
    }

    private function normalizeImagePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->flatten()
                ->first(
                    fn ($item): bool =>
                        is_string($item)
                        && trim($item) !== ''
                );
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (
            str_starts_with($value, '[')
            || str_starts_with($value, '{')
        ) {
            try {
                $decoded = json_decode(
                    $value,
                    true,
                    flags: JSON_THROW_ON_ERROR
                );

                return $this->normalizeImagePath($decoded);
            } catch (\Throwable) {
                // Keep the original string.
            }
        }

        return trim($value, "\"'");
    }

    private function firstFilled(array $values): mixed
    {
        foreach ($values as $value) {
            if (
                is_array($value)
                && collect($value)->flatten()->filter()->isNotEmpty()
            ) {
                return $value;
            }

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function paymentSettings(): ?object
    {
        try {
            if (
                ! class_exists(PaymentSetting::class)
                || ! Schema::hasTable('payment_settings')
            ) {
                return null;
            }

            return PaymentSetting::current();
        } catch (\Throwable) {
            return null;
        }
    }

    private function setting(
        string $key,
        mixed $default = null
    ): mixed {
        try {
            return function_exists('setting')
                ? setting($key, $default)
                : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    private function safeColor(
        mixed $color,
        string $fallback
    ): string {
        $color = trim((string) $color);

        return preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $color
        )
            ? $color
            : $fallback;
    }
}
