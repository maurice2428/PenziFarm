<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('customer_number')->unique();

            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('kra_pin')->nullable()->index();
            $table->string('id_number')->nullable();

            $table->enum('customer_type', [
                'individual',
                'company',
                'farm',
                'butcher',
                'broker',
                'institution',
                'other',
            ])->default('individual');

            $table->string('country')->nullable()->default('Kenya');
            $table->string('county')->nullable();
            $table->string('town')->nullable();
            $table->text('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('place_label')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_type', 'is_active']);
            $table->index(['county', 'town']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
