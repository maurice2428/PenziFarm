<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_c2_b_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('mpesa_c2_b_settings', 'short_code')) {
                $table->string('short_code')->nullable()->after('id');
            }

            if (! Schema::hasColumn('mpesa_c2_b_settings', 'environment')) {
                $table->string('environment')->default('sandbox')->after('short_code');
            }

            if (! Schema::hasColumn('mpesa_c2_b_settings', 'validation_url')) {
                $table->string('validation_url')->nullable()->after('environment');
            }

            if (! Schema::hasColumn('mpesa_c2_b_settings', 'confirmation_url')) {
                $table->string('confirmation_url')->nullable()->after('validation_url');
            }

            if (! Schema::hasColumn('mpesa_c2_b_settings', 'response_type')) {
                $table->string('response_type')->default('Completed')->after('confirmation_url');
            }

            if (! Schema::hasColumn('mpesa_c2_b_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('response_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_c2_b_settings', function (Blueprint $table) {
            $table->dropColumn([
                'short_code',
                'environment',
                'validation_url',
                'confirmation_url',
                'response_type',
                'is_active',
            ]);
        });
    }
};
