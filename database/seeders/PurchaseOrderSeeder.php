<?php

namespace Database\Seeders;

use App\Models\HealthProduct;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::first();

        $po = PurchaseOrder::create([
            'purchase_order_number' => 'PO202605200001',
            'supplier_id' => $supplier->id,
            'order_date' => now()->subDays(3),
            'expected_delivery_date' => now()->addDays(2),
            'status' => 'received',
            'payment_status' => 'partial',
            'payment_method' => 'mpesa_b2b',
            'amount_paid' => 25000,
            'tax_amount' => 0,
            'discount_amount' => 0,
        ]);

        $products = HealthProduct::take(3)->get();

        foreach ($products as $product) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'health_product_id' => $product->id,
                'inventory_item_id' => $product->inventory_item_id,
                'quantity_ordered' => rand(50, 300),
                'quantity_received' => rand(50, 300),
                'unit_cost' => rand(200, 5000),
                'line_total' => rand(20000, 150000),
                'batch_number' => 'BATCH-' . rand(1000, 9999),
                'expiry_date' => now()->addMonths(rand(6, 24)),
            ]);
        }

        $po->recalculateTotals();
        $po->receiveStock();
    }
}
