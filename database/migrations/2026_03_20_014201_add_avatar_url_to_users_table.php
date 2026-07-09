<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $column = config('filament-edit-profile.avatar_column', 'avatar_url');

        if (! Schema::hasColumn('users', $column)) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->string($column)->nullable();
            });
        }
    }

    public function down(): void
    {
        $column = config('filament-edit-profile.avatar_column', 'avatar_url');

        if (Schema::hasColumn('users', $column)) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
