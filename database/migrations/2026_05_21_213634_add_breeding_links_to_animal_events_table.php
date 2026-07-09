<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('animal_events')) {
            return;
        }

        Schema::table('animal_events', function (Blueprint $table) {
            if (!Schema::hasColumn('animal_events', 'breeding_batch_id')) {
                $table
                    ->foreignId('breeding_batch_id')
                    ->nullable()
                    ->after('location_id')
                    ->constrained('breeding_batches')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('animal_events', 'breeding_record_id')) {
                $table
                    ->foreignId('breeding_record_id')
                    ->nullable()
                    ->after('breeding_batch_id')
                    ->constrained('breeding_records')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('animal_events')) {
            return;
        }

        Schema::table('animal_events', function (Blueprint $table) {
            if (Schema::hasColumn('animal_events', 'breeding_record_id')) {
                $table->dropConstrainedForeignId('breeding_record_id');
            }

            if (Schema::hasColumn('animal_events', 'breeding_batch_id')) {
                $table->dropConstrainedForeignId('breeding_batch_id');
            }
        });
    }
};
