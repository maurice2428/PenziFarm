<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->date('date_died')->nullable()->after('status');
            $table->string('cause_of_death')->nullable()->after('date_died');
            $table->text('death_comments')->nullable()->after('cause_of_death');

            $table->date('date_culled')->nullable()->after('death_comments');
            $table->string('culling_reason')->nullable()->after('date_culled');
            $table->text('culling_comments')->nullable()->after('culling_reason');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn([
                'date_died',
                'cause_of_death',
                'death_comments',
                'date_culled',
                'culling_reason',
                'culling_comments',
            ]);
        });
    }
};
