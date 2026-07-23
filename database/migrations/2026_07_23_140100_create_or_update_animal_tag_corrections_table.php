<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('animal_tag_corrections')) {
            Schema::create('animal_tag_corrections', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('animal_id')->index();
                $table->string('old_tag_number')->nullable()->index();
                $table->string('new_tag_number')->index();
                $table->unsignedBigInteger('old_breed_id')->nullable()->index();
                $table->unsignedBigInteger('new_breed_id')->nullable()->index();
                $table->date('old_date_of_birth')->nullable();
                $table->date('new_date_of_birth')->nullable();
                $table->string('correction_type', 50)->default('tag_sequence');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('corrected_by')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        $columns = [
            'animal_id',
            'old_tag_number',
            'new_tag_number',
            'old_breed_id',
            'new_breed_id',
            'old_date_of_birth',
            'new_date_of_birth',
            'correction_type',
            'reason',
            'corrected_by',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('animal_tag_corrections', $column)) {
                continue;
            }

            Schema::table('animal_tag_corrections', function (Blueprint $table) use ($column): void {
                match ($column) {
                    'animal_id' => $table->unsignedBigInteger($column)->nullable()->index(),
                    'old_tag_number', 'new_tag_number' => $table->string($column)->nullable()->index(),
                    'old_breed_id', 'new_breed_id', 'corrected_by' => $table->unsignedBigInteger($column)->nullable()->index(),
                    'old_date_of_birth', 'new_date_of_birth' => $table->date($column)->nullable(),
                    'correction_type' => $table->string($column, 50)->nullable(),
                    'reason' => $table->text($column)->nullable(),
                    'created_at', 'updated_at' => $table->timestamp($column)->nullable(),
                    default => null,
                };
            });
        }
    }

    public function down(): void
    {
        // Audit history is intentionally retained on rollback.
    }
};
