<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('directory_id')->nullable()->constrained('data_directories')->nullOnDelete();

            $table->string('title');
            $table->string('document_type')->default('Document');

            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->text('description')->nullable();

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['directory_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_documents');
    }
};
