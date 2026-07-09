<?php

namespace Database\Seeders;

use App\Models\CropCatalog;
use Illuminate\Database\Seeder;

class CropCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            [
                'name' => 'Maize',
                'variety' => 'General',
                'category' => 'cereal',
                'crop_type' => 'annual',
                'germination_days_min' => 4,
                'germination_days_max' => 7,
                'maturity_days_min' => 90,
                'maturity_days_max' => 150,
                'harvest_window_days' => 21,
                'spacing_between_rows_cm' => 75,
                'spacing_between_plants_cm' => 25,
                'seed_rate_per_acre' => 10,
                'seed_rate_unit' => 'kg',
                'expected_yield_per_acre' => 25,
                'yield_unit' => '90kg bags',
                'water_requirement' => 'Moderate',
                'soil_requirement' => 'Well-drained fertile loam soil',
                'care_routine' => 'Scout weekly. Weed early. Top dress after establishment. Monitor fall armyworm and stalk borer.',
                'fertilizer_routine' => 'Use planting fertiliser during planting, then top dress with CAN/Urea around 3-5 weeks depending on crop condition.',
                'spray_routine' => 'Spray only when pest/disease threshold is observed. Avoid unnecessary chemical use.',
                'harvest_notes' => 'Harvest when cobs are mature and grain moisture is suitable.',
                'supports_nursery' => false,
                'is_perennial' => false,
            ],
            [
                'name' => 'Avocado',
                'variety' => 'Hass',
                'category' => 'fruit_tree',
                'crop_type' => 'orchard',
                'germination_days_min' => 21,
                'germination_days_max' => 45,
                'transplant_days' => 180,
                'maturity_days_min' => 1095,
                'maturity_days_max' => 1460,
                'harvest_window_days' => 90,
                'spacing_between_rows_cm' => 700,
                'spacing_between_plants_cm' => 700,
                'expected_yield_per_acre' => 0,
                'yield_unit' => 'kg',
                'water_requirement' => 'Moderate to high during establishment',
                'soil_requirement' => 'Deep, well-drained soil. Avoid waterlogging.',
                'care_routine' => 'Mulch, irrigate, prune, scout for pests, and monitor tree establishment.',
                'fertilizer_routine' => 'Apply manure and balanced fertiliser based on tree age and soil test.',
                'spray_routine' => 'Scout for mites, thrips, fungal issues, and apply recommended control only where needed.',
                'harvest_notes' => 'Harvest mature fruits carefully to avoid bruising.',
                'supports_nursery' => true,
                'is_perennial' => true,
            ],
            [
                'name' => 'Avocado Seedlings',
                'variety' => 'Nursery',
                'category' => 'nursery',
                'crop_type' => 'nursery',
                'germination_days_min' => 21,
                'germination_days_max' => 45,
                'transplant_days' => 180,
                'maturity_days_min' => null,
                'maturity_days_max' => null,
                'harvest_window_days' => null,
                'water_requirement' => 'Frequent light watering',
                'soil_requirement' => 'Sterile nursery media with good drainage',
                'care_routine' => 'Water regularly, provide shade, prevent damping-off, remove weak seedlings, harden seedlings before transplanting.',
                'fertilizer_routine' => 'Use light nursery feeding depending on seedling vigor.',
                'spray_routine' => 'Prevent damping-off and scout regularly for pests.',
                'harvest_notes' => 'Move only healthy hardened seedlings for transplanting.',
                'supports_nursery' => true,
                'is_perennial' => true,
            ],
            [
                'name' => 'Napier Grass',
                'variety' => 'General',
                'category' => 'fodder',
                'crop_type' => 'perennial',
                'germination_days_min' => null,
                'germination_days_max' => null,
                'maturity_days_min' => 75,
                'maturity_days_max' => 120,
                'harvest_window_days' => 30,
                'water_requirement' => 'Moderate',
                'soil_requirement' => 'Fertile well-drained soil',
                'care_routine' => 'Weed early, manure well, cut at suitable height, and maintain regrowth.',
                'fertilizer_routine' => 'Apply manure and nitrogen after cutting if needed.',
                'spray_routine' => 'Scout for pests and diseases.',
                'harvest_notes' => 'Cut before excessive lignification for better feed quality.',
                'supports_nursery' => false,
                'is_perennial' => true,
            ],
        ];

        foreach ($crops as $crop) {
            CropCatalog::query()->firstOrCreate(
                [
                    'name' => $crop['name'],
                    'variety' => $crop['variety'],
                ],
                [
                    ...$crop,
                    'is_active' => true,
                ]
            );
        }
    }
}
