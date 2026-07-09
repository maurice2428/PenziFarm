<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();

            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();

            $table->date('transfer_date');
            $table->date('expected_receive_date')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->string('status')->default('pending');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('receive_notes')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->unsignedBigInteger('prepared_by_id')->nullable();
            $table->unsignedBigInteger('received_by_id')->nullable();
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'transfer_date']);
            $table->index(['from_location_id', 'to_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_transfers');
    }
};
