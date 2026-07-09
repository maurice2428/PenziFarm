<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Tylosin Injection',
                'category' => 'treatment',
                'unit' => 'ml',
                'opening_stock' => 5000,
                'reorder_level' => 500,
                'order_level' => 1000,
                'unit_cost' => 25,
                'is_active' => true,
            ],

            [
                'name' => 'Albendazole Dewormer',
                'category' => 'dewormer',
                'unit' => 'ml',
                'opening_stock' => 3000,
                'reorder_level' => 300,
                'order_level' => 800,
                'unit_cost' => 18,
                'is_active' => true,
            ],

            [
                'name' => 'Sidai Acaricide',
                'category' => 'dip',
                'unit' => 'litres',
                'opening_stock' => 200,
                'reorder_level' => 20,
                'order_level' => 50,
                'unit_cost' => 4200,
                'is_active' => true,
            ],

            [
                'name' => 'PPR Vaccine',
                'category' => 'vaccine',
                'unit' => 'ml',
                'opening_stock' => 10000,
                'reorder_level' => 1000,
                'order_level' => 2500,
                'unit_cost' => 15,
                'is_active' => true,
            ],

            [
                'name' => 'Bomarhodes Hay',
                'category' => 'feed',
                'unit' => 'bales',
                'opening_stock' => 625,
                'reorder_level' => 50,
                'order_level' => 120,
                'unit_cost' => 350,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::create($item);
        }
    }
}
