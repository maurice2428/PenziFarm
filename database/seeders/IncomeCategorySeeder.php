<?php

namespace Database\Seeders;

use App\Models\Sales\IncomeCategory;
use Illuminate\Database\Seeder;

class IncomeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Animal Sales',
                'type' => 'animal_sales',
                'description' => 'General income from livestock sales.',
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Breeder Sales',
                'type' => 'breeder_sales',
                'description' => 'Income from animals sold specifically as breeders.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Cull Sales',
                'type' => 'cull_sales',
                'description' => 'Income from culled animals.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Slaughter Sales',
                'type' => 'slaughter_sales',
                'description' => 'Income from animals sold for slaughter or meat.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Milk Sales',
                'type' => 'milk_sales',
                'description' => 'Income from milk sales.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Egg Sales',
                'type' => 'egg_sales',
                'description' => 'Income from egg sales.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Crop Sales',
                'type' => 'crop_sales',
                'description' => 'Income from crops, vegetables, fruits, or farm produce.',
                'sort_order' => 7,
            ],
            [
                'name' => 'Manure Sales',
                'type' => 'manure_sales',
                'description' => 'Income from manure sales.',
                'sort_order' => 8,
            ],
            [
                'name' => 'Service Income',
                'type' => 'service_income',
                'description' => 'Income from services offered by the farm.',
                'sort_order' => 9,
            ],
            [
                'name' => 'Other Income',
                'type' => 'other_income',
                'description' => 'Other miscellaneous farm income.',
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $category) {
            IncomeCategory::updateOrCreate(
                ['name' => $category['name']],
                $category + ['is_active' => true]
            );
        }
    }
}
