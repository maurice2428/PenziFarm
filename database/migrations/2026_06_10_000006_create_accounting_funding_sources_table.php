<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_funding_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['director_capital', 'director_loan', 'grant', 'bank_loan', 'operations', 'investor', 'other'])->default('director_capital');
            $table->foreignId('linked_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_funding_sources');
    }
};
