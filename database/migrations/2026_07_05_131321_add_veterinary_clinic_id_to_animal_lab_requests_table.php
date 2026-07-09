<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('animal_lab_requests') &&
            ! Schema::hasColumn('animal_lab_requests', 'veterinary_clinic_id')
        ) {
            Schema::table('animal_lab_requests', function (Blueprint $table) {
                $table->foreignId('veterinary_clinic_id')
                    ->nullable()
                    ->after('clinic_name')
                    ->constrained('veterinary_clinics')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('animal_lab_requests') &&
            Schema::hasColumn('animal_lab_requests', 'veterinary_clinic_id')
        ) {
            Schema::table('animal_lab_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('veterinary_clinic_id');
            });
        }
    }
};
