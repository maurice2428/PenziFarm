<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'nationality')) {
                $table->string('nationality', 80)->default('Kenyan')->after('gender');
            }

            if (! Schema::hasColumn('employees', 'place_of_birth')) {
                $table->string('place_of_birth', 120)->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('employees', 'id_document_front_path')) {
                $table->string('id_document_front_path')->nullable()->after('avatar_path');
            }

            if (! Schema::hasColumn('employees', 'id_document_back_path')) {
                $table->string('id_document_back_path')->nullable()->after('id_document_front_path');
            }

            if (! Schema::hasColumn('employees', 'is_tax_resident')) {
                $table->boolean('is_tax_resident')->default(true)->after('tax_enabled');
            }

            if (! Schema::hasColumn('employees', 'insurance_premium')) {
                $table->decimal('insurance_premium', 14, 2)->default(0)->after('insurance_relief');
            }

            if (! Schema::hasColumn('employees', 'tax_exemption_number')) {
                $table->string('tax_exemption_number', 80)->nullable()->after('insurance_premium');
            }

            if (! Schema::hasColumn('employees', 'tax_exemption_expiry')) {
                $table->date('tax_exemption_expiry')->nullable()->after('tax_exemption_number');
            }

            if (! Schema::hasColumn('employees', 'tax_supporting_document_path')) {
                $table->string('tax_supporting_document_path')->nullable()->after('tax_exemption_expiry');
            }
        });
    }

    public function down(): void
    {
        $columns = [
            'nationality',
            'place_of_birth',
            'id_document_front_path',
            'id_document_back_path',
            'is_tax_resident',
            'insurance_premium',
            'tax_exemption_number',
            'tax_exemption_expiry',
            'tax_supporting_document_path',
        ];

        Schema::table('employees', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
