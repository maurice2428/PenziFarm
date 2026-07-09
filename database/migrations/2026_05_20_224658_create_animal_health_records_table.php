<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_health_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type'); // vaccination, deworming, dipping, treatment
            $table->date('recorded_at');

            $table->decimal('quantity_used', 14, 2)->default(0);
            $table->string('dosage')->nullable();
            $table->string('method')->nullable(); // injection, oral, spray, dip, pour-on

            $table->string('administered_by')->nullable();
            $table->date('next_due_date')->nullable();

            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'type']);
            $table->index(['recorded_at', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_health_records');
    }
};
