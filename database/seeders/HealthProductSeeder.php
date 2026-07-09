<?php

namespace Database\Seeders;

use App\Models\HealthProduct;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class HealthProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'PPR Vaccine',
                'type' => 'vaccine',
                'species' => 'Sheep',
                'dosage_per_animal' => 2,
                'dosage_unit' => 'ml',
                'administration_method' => 'injection',
                'frequency' => 'annually',
                'frequency_days' => 365,
            ],

            [
                'name' => 'Albendazole Dewormer',
                'type' => 'dewormer',
                'species' => 'Goat',
                'dosage_per_animal' => 5,
                'dosage_unit' => 'ml',
                'administration_method' => 'oral',
                'frequency' => 'quarterly',
                'frequency_days' => 90,
            ],

            [
                'name' => 'Sidai Acaricide',
                'type' => 'dip',
                'species' => 'All',
                'dosage_per_animal' => 15,
                'dosage_unit' => 'ml',
                'administration_method' => 'dip',
                'frequency' => 'monthly',
                'frequency_days' => 30,
            ],

            [
                'name' => 'Tylosin Injection',
                'type' => 'treatment',
                'species' => 'Sheep',
                'dosage_per_animal' => 10,
                'dosage_unit' => 'ml',
                'administration_method' => 'injection',
                'frequency' => 'once',
                'frequency_days' => 0,
            ],
        ];

        foreach ($products as $product) {
            $inventoryItem = InventoryItem::where('name', $product['name'])->first();

            HealthProduct::create([
                ...$product,
                'inventory_item_id' => $inventoryItem?->id,
                'status' => 'active',
            ]);
        }
    }
}
