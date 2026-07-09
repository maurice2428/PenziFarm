<?php

namespace Database\Seeders;

use App\Models\BreedingGestationRule;
use Illuminate\Database\Seeder;

class BreedingGestationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['species' => 'Sheep', 'gestation_days' => 147],
            ['species' => 'Goat', 'gestation_days' => 150],
            ['species' => 'Cattle', 'gestation_days' => 283],
        ];

        foreach ($rules as $rule) {
            BreedingGestationRule::query()->updateOrCreate(
                [
                    'species' => $rule['species'],
                    'breed_id' => null,
                ],
                [
                    'gestation_days' => $rule['gestation_days'],
                    'is_active' => true,
                    'notes' => 'Default gestation period for ' . $rule['species'],
                ]
            );
        }
    }
}
