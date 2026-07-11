<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('breeding_batches')) {
            return;
        }

        Schema::table(
            'breeding_batches',
            function (Blueprint $table): void {
                if (
                    ! Schema::hasColumn(
                        'breeding_batches',
                        'archived_by'
                    )
                ) {
                    $table
                        ->foreignId('archived_by')
                        ->nullable()
                        ->after('created_by')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (
                    ! Schema::hasColumn(
                        'breeding_batches',
                        'archive_reason'
                    )
                ) {
                    $table
                        ->text('archive_reason')
                        ->nullable()
                        ->after('archived_by');
                }
            }
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('breeding_batches')) {
            return;
        }

        if (
            Schema::hasColumn(
                'breeding_batches',
                'archived_by'
            )
        ) {
            Schema::table(
                'breeding_batches',
                function (Blueprint $table): void {
                    $table->dropConstrainedForeignId(
                        'archived_by'
                    );
                }
            );
        }

        if (
            Schema::hasColumn(
                'breeding_batches',
                'archive_reason'
            )
        ) {
            Schema::table(
                'breeding_batches',
                function (Blueprint $table): void {
                    $table->dropColumn('archive_reason');
                }
            );
        }
    }
};
