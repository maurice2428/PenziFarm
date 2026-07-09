<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->nullable()->unique();
                $table->string('type')->default('station');
                $table->string('address')->nullable();
                $table->string('county')->nullable();
                $table->string('sub_county')->nullable();
                $table->string('ward')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->text('place_label')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        } else {
            Schema::table('locations', function (Blueprint $table): void {
                if (! Schema::hasColumn('locations', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }

                if (! Schema::hasColumn('locations', 'type')) {
                    $table->string('type')->default('station')->after('code');
                }

                if (! Schema::hasColumn('locations', 'address')) {
                    $table->string('address')->nullable()->after('type');
                }

                if (! Schema::hasColumn('locations', 'county')) {
                    $table->string('county')->nullable()->after('address');
                }

                if (! Schema::hasColumn('locations', 'sub_county')) {
                    $table->string('sub_county')->nullable()->after('county');
                }

                if (! Schema::hasColumn('locations', 'ward')) {
                    $table->string('ward')->nullable()->after('sub_county');
                }

                if (! Schema::hasColumn('locations', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('ward');
                }

                if (! Schema::hasColumn('locations', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }

                if (! Schema::hasColumn('locations', 'place_label')) {
                    $table->text('place_label')->nullable()->after('longitude');
                }

                if (! Schema::hasColumn('locations', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('place_label');
                }

                if (! Schema::hasColumn('locations', 'is_default')) {
                    $table->boolean('is_default')->default(false)->after('is_active');
                }

                if (! Schema::hasColumn('locations', 'notes')) {
                    $table->text('notes')->nullable()->after('is_default');
                }

                if (! Schema::hasColumn('locations', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('notes')
                        ->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('locations', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')
                        ->constrained('users')->nullOnDelete();
                }
            });

            if (! Schema::hasColumn('locations', 'created_at')) {
                Schema::table('locations', function (Blueprint $table): void {
                    $table->timestamps();
                });
            }
        }

        if (! Schema::hasColumn('animals', 'current_location_id')) {
            Schema::table('animals', function (Blueprint $table): void {
                $table->foreignId('current_location_id')
                    ->nullable()
                    ->after('dam_id')
                    ->constrained('locations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('animals') && Schema::hasColumn('animals', 'current_location_id')) {
            Schema::table('animals', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('current_location_id');
            });
        }

        // Locations are retained on rollback because production farm data may exist.
    }
};
