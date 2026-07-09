<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('purity_breed_id')
                ->nullable()
                ->after('breed_id')
                ->constrained('breeds')
                ->nullOnDelete();

            $table->decimal('breed_purity_percent', 7, 4)
                ->nullable()
                ->after('purity_breed_id');

            $table->decimal('purity_override_percent', 7, 4)
                ->nullable()
                ->after('breed_purity_percent');

            $table->string('purity_status', 40)
                ->default('pending')
                ->after('purity_override_percent');

            $table->boolean('is_foundation_animal')
                ->default(false)
                ->after('purity_status');

            $table->date('purity_verified_at')
                ->nullable()
                ->after('is_foundation_animal');

            $table->text('purity_notes')
                ->nullable()
                ->after('purity_verified_at');

            $table->index(
                ['purity_breed_id', 'purity_status'],
                'animals_purity_breed_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex('animals_purity_breed_status_index');

            $table->dropConstrainedForeignId('purity_breed_id');

            $table->dropColumn([
                'breed_purity_percent',
                'purity_override_percent',
                'purity_status',
                'is_foundation_animal',
                'purity_verified_at',
                'purity_notes',
            ]);
        });
    }
};
