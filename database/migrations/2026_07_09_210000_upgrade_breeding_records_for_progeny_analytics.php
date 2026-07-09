<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('breeding_records')) {
            return;
        }

        $columns = Schema::getColumnListing('breeding_records');

        Schema::table('breeding_records', function (Blueprint $table) use ($columns): void {
            if (! in_array('birth_outcome', $columns, true)) {
                $table->string('birth_outcome')->default('pending')->after('delivery_notes');
            }

            if (! in_array('birth_assistance', $columns, true)) {
                $table->string('birth_assistance')->nullable()->after('birth_outcome');
            }

            if (! in_array('live_birth_count', $columns, true)) {
                $table->unsignedSmallInteger('live_birth_count')->default(0)->after('birth_assistance');
            }

            if (! in_array('stillborn_count', $columns, true)) {
                $table->unsignedSmallInteger('stillborn_count')->default(0)->after('live_birth_count');
            }

            if (! in_array('neonatal_death_count', $columns, true)) {
                $table->unsignedSmallInteger('neonatal_death_count')->default(0)->after('stillborn_count');
            }

            if (! in_array('weaned_count', $columns, true)) {
                $table->unsignedSmallInteger('weaned_count')->default(0)->after('neonatal_death_count');
            }

            if (! in_array('retained_breeding_count', $columns, true)) {
                $table->unsignedSmallInteger('retained_breeding_count')->default(0)->after('weaned_count');
            }

            if (! in_array('mothering_score', $columns, true)) {
                $table->decimal('mothering_score', 4, 2)->nullable()->after('retained_breeding_count');
            }

            if (! in_array('milk_score', $columns, true)) {
                $table->decimal('milk_score', 4, 2)->nullable()->after('mothering_score');
            }

            if (! in_array('temperament_score', $columns, true)) {
                $table->decimal('temperament_score', 4, 2)->nullable()->after('milk_score');
            }

            if (! in_array('offspring_vigour_score', $columns, true)) {
                $table->decimal('offspring_vigour_score', 4, 2)->nullable()->after('temperament_score');
            }

            if (! in_array('maternal_notes', $columns, true)) {
                $table->text('maternal_notes')->nullable()->after('offspring_vigour_score');
            }

            if (! in_array('evaluation_completed_at', $columns, true)) {
                $table->timestamp('evaluation_completed_at')->nullable()->after('maternal_notes');
            }

            if (! in_array('evaluation_completed_by', $columns, true)) {
                $table->foreignId('evaluation_completed_by')
                    ->nullable()
                    ->after('evaluation_completed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('breeding_records')) {
            return;
        }

        if (Schema::hasColumn('breeding_records', 'evaluation_completed_by')) {
            Schema::table('breeding_records', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('evaluation_completed_by');
            });
        }

        $columns = [
            'birth_outcome',
            'birth_assistance',
            'live_birth_count',
            'stillborn_count',
            'neonatal_death_count',
            'weaned_count',
            'retained_breeding_count',
            'mothering_score',
            'milk_score',
            'temperament_score',
            'offspring_vigour_score',
            'maternal_notes',
            'evaluation_completed_at',
        ];

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('breeding_records', $column)
        ));

        if ($existing !== []) {
            Schema::table('breeding_records', function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            });
        }
    }
};
