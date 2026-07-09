<?php

namespace Database\Seeders;

use App\Models\CropCareTask;
use App\Models\CropCatalog;
use App\Models\CropSeason;
use App\Models\FarmField;
use App\Models\FieldPartition;
use App\Models\NurseryBatch;
use Illuminate\Database\Seeder;

class CropDemoSeeder extends Seeder
{
    public function run(): void
    {
        $maize = CropCatalog::query()->firstOrCreate(
            [
                'name' => 'Maize',
                'variety' => 'H614D',
            ],
            [
                'category' => 'cereal',
                'crop_type' => 'annual',
                'germination_days_min' => 4,
                'germination_days_max' => 7,
                'maturity_days_min' => 110,
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
                'care_routine' => 'Scout weekly, weed early, top dress after establishment, and monitor fall armyworm.',
                'fertilizer_routine' => 'Use planting fertiliser during planting, then top dress with CAN/Urea after establishment.',
                'spray_routine' => 'Spray only when pest or disease threshold is observed.',
                'harvest_notes' => 'Harvest when cobs are mature and grain moisture is suitable.',
                'supports_nursery' => false,
                'is_perennial' => false,
                'is_active' => true,
            ]
        );

        $avocado = CropCatalog::query()->firstOrCreate(
            [
                'name' => 'Avocado',
                'variety' => 'Hass',
            ],
            [
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
                'spray_routine' => 'Scout for mites, thrips, fungal issues, and apply recommended control where needed.',
                'harvest_notes' => 'Harvest mature fruits carefully to avoid bruising.',
                'supports_nursery' => true,
                'is_perennial' => true,
                'is_active' => true,
            ]
        );

        $napier = CropCatalog::query()->firstOrCreate(
            [
                'name' => 'Napier Grass',
                'variety' => 'General',
            ],
            [
                'category' => 'fodder',
                'crop_type' => 'perennial',
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
                'is_active' => true,
            ]
        );

        $field = FarmField::query()->firstOrCreate(
            ['name' => 'Dhiwa Farm'],
            [
                'total_area' => 25,
                'area_unit' => 'acre',
                'status' => 'active',
                'soil_type' => 'Loam',
                'irrigation_type' => 'Rainfed',
            ]
        );

        $blockA = FieldPartition::query()->firstOrCreate(
            [
                'farm_field_id' => $field->id,
                'name' => 'Block A - Maize',
            ],
            [
                'area' => 8,
                'area_unit' => 'acre',
                'status' => 'planted',
            ]
        );

        $blockB = FieldPartition::query()->firstOrCreate(
            [
                'farm_field_id' => $field->id,
                'name' => 'Block B - Napier',
            ],
            [
                'area' => 5,
                'area_unit' => 'acre',
                'status' => 'planted',
            ]
        );

        $nurseryBed = FieldPartition::query()->firstOrCreate(
            [
                'farm_field_id' => $field->id,
                'name' => 'Nursery Bed 1',
            ],
            [
                'area' => 0.5,
                'area_unit' => 'acre',
                'status' => 'nursery',
            ]
        );

        $maizeSeason = CropSeason::query()->firstOrCreate(
            ['name' => 'Dhiwa Maize Season May 2026'],
            [
                'crop_catalog_id' => $maize->id,
                'farm_field_id' => $field->id,
                'field_partition_id' => $blockA->id,
                'planting_type' => 'direct_seed',
                'start_date' => now('Africa/Nairobi')->subDays(32)->toDateString(),
                'planting_date' => now('Africa/Nairobi')->subDays(30)->toDateString(),
                'actual_germination_date' => now('Africa/Nairobi')->subDays(23)->toDateString(),
                'germination_percent' => 89,
                'area_planted' => 8,
                'area_unit' => 'acre',
                'plant_population' => 17600,
                'growth_stage' => 'vegetative',
                'health_status' => 'good',
                'status' => 'active',
                'total_input_cost' => 54000,
                'estimated_harvest_value' => 145000,
                'notes' => 'Demo maize crop showing vegetative stage.',
            ]
        );

        $avocadoSeason = CropSeason::query()->firstOrCreate(
            ['name' => 'Avocado Orchard Establishment'],
            [
                'crop_catalog_id' => $avocado->id,
                'farm_field_id' => $field->id,
                'field_partition_id' => $blockA->id,
                'planting_type' => 'orchard',
                'start_date' => now('Africa/Nairobi')->subDays(90)->toDateString(),
                'planting_date' => now('Africa/Nairobi')->subDays(80)->toDateString(),
                'area_planted' => 2,
                'area_unit' => 'acre',
                'plant_population' => 220,
                'growth_stage' => 'vegetative',
                'health_status' => 'fair',
                'status' => 'active',
                'total_input_cost' => 72000,
                'estimated_harvest_value' => 210000,
                'notes' => 'Demo avocado establishment block.',
            ]
        );

        $napierSeason = CropSeason::query()->firstOrCreate(
            ['name' => 'Napier Establishment Block B'],
            [
                'crop_catalog_id' => $napier->id,
                'farm_field_id' => $field->id,
                'field_partition_id' => $blockB->id,
                'planting_type' => 'transplant',
                'start_date' => now('Africa/Nairobi')->subDays(50)->toDateString(),
                'planting_date' => now('Africa/Nairobi')->subDays(48)->toDateString(),
                'area_planted' => 5,
                'area_unit' => 'acre',
                'growth_stage' => 'vegetative',
                'health_status' => 'excellent',
                'status' => 'active',
                'total_input_cost' => 35000,
                'estimated_harvest_value' => 90000,
                'notes' => 'Demo napier fodder block.',
            ]
        );

        $nurseryBatch = NurseryBatch::query()->firstOrCreate(
            ['name' => 'Hass Avocado Seedlings Batch 01'],
            [
                'crop_catalog_id' => $avocado->id,
                'farm_field_id' => $field->id,
                'field_partition_id' => $nurseryBed->id,
                'sowing_date' => now('Africa/Nairobi')->subDays(40)->toDateString(),
                'seed_quantity' => 10,
                'seed_unit' => 'kg',
                'initial_seedlings' => 1200,
                'germinated_seedlings' => 1050,
                'healthy_seedlings' => 980,
                'weak_seedlings' => 40,
                'dead_seedlings' => 30,
                'transplanted_seedlings' => 0,
                'growth_stage' => 'hardening',
                'status' => 'active',
                'notes' => 'Demo avocado nursery batch.',
            ]
        );

        $tasks = [
            [
                'crop_season_id' => $maizeSeason->id,
                'crop_catalog_id' => $maize->id,
                'due_date' => now('Africa/Nairobi')->addDays(1)->toDateString(),
                'task_type' => 'scouting',
                'title' => 'Scout maize for fall armyworm',
                'instructions' => 'Check leaf damage, whorls, and pest presence before deciding whether to spray.',
            ],
            [
                'crop_season_id' => $maizeSeason->id,
                'crop_catalog_id' => $maize->id,
                'due_date' => now('Africa/Nairobi')->addDays(3)->toDateString(),
                'task_type' => 'fertilizer',
                'title' => 'Review maize top dressing',
                'instructions' => 'Check crop colour and growth. Apply CAN/Urea only where crop condition justifies.',
            ],
            [
                'nursery_batch_id' => $nurseryBatch->id,
                'crop_catalog_id' => $avocado->id,
                'due_date' => now('Africa/Nairobi')->addDays(2)->toDateString(),
                'task_type' => 'nursery_care',
                'title' => 'Harden avocado seedlings',
                'instructions' => 'Reduce shade gradually and prepare seedlings for transplant readiness.',
            ],
            [
                'crop_season_id' => $napierSeason->id,
                'crop_catalog_id' => $napier->id,
                'due_date' => now('Africa/Nairobi')->addDays(5)->toDateString(),
                'task_type' => 'weeding',
                'title' => 'Weed napier establishment block',
                'instructions' => 'Remove competing weeds and assess regrowth.',
            ],
        ];

        foreach ($tasks as $task) {
            CropCareTask::query()->firstOrCreate(
                [
                    'due_date' => $task['due_date'],
                    'title' => $task['title'],
                ],
                [
                    ...$task,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]
            );
        }
    }
}
