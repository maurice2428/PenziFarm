<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\HealthAdministration;
use App\Models\HealthProduct;
use Illuminate\Database\Seeder;

class HealthAdministrationSeeder extends Seeder
{
    public function run(): void
    {
        $animals = Animal::where('status', 'Active')->take(20)->pluck('id');

        if ($animals->isEmpty()) {
            return;
        }

        $products = HealthProduct::all();

        foreach ($products as $product) {
            $count = rand(5, 20);

            $administration = HealthAdministration::create([
                'health_product_id' => $product->id,
                'administered_at' => now()->subDays(rand(1, 30)),
                'animal_count' => $count,
                'dosage_per_animal' => $product->dosage_per_animal,
                'total_quantity_used' => $count * (float) $product->dosage_per_animal,
                'next_due_date' => now()->addDays($product->frequency_days ?? 30),
                'administered_by' => 'Dr. Maurice',
                'notes' => 'Routine farm treatment and preventive administration.',
            ]);

            $administration->animals()->sync(
                $animals->random(min($count, $animals->count()))
            );
        }
    }
}
