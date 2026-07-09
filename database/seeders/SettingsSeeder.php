<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Farm
            'farm.name' => 'Lelekwe Farms',
            'farm.tagline' => 'Nurturing Nature, Feeding The Future',
            'farm.phone' => '+254743487186',
            'farm.email' => 'jambo@lelekwefarms.co.ke',
            'farm.country' => 'Kenya',
            'farm.county' => 'Baringo',
            'farm.lat' => null,
            'farm.lng' => null,

            // Branding
            'branding.logo_light' => null,
            'branding.logo_dark' => null,
            'branding.favicon' => null,

            // Theme
            'theme.primary' => '#16a34a',
            'theme.secondary' => '#14532d',
            'theme.accent' => '#f59e0b',
            'theme.danger' => '#dc2626',
            'theme.success' => '#16a34a',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => 'string',
                ]
            );
        }
    }
}
