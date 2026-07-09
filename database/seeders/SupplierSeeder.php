<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'company_name' => 'Sidai Agrovet Supplies',
                'contact_person' => 'James Kiptoo',
                'phone_primary' => '+254712000111',
                'email' => 'sales@sidaiagrovet.co.ke',
                'physical_address' => 'Nakuru Town',
                'status' => 'active',
            ],

            [
                'company_name' => 'VetCare Kenya',
                'contact_person' => 'Ann Wambui',
                'phone_primary' => '+254722111333',
                'email' => 'orders@vetcare.co.ke',
                'physical_address' => 'Eldoret',
                'status' => 'active',
            ],

            [
                'company_name' => 'Molo Feeds Ltd',
                'contact_person' => 'David Mutai',
                'phone_primary' => '+254733444555',
                'email' => 'info@molofeeds.co.ke',
                'physical_address' => 'Molo',
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
