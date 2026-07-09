<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();

            $table->enum('mpesa_environment', ['sandbox', 'live'])->default('sandbox');
            $table->string('mpesa_consumer_key')->nullable();
            $table->text('mpesa_consumer_secret')->nullable();
            $table->string('mpesa_shortcode')->nullable();
            $table->text('mpesa_passkey')->nullable();
            $table->string('mpesa_callback_url')->nullable();
            $table->string('mpesa_account_reference_prefix')->default('LLK');
            $table->string('mpesa_transaction_description')->default('Lelekwe Farm Payment');

            $table->boolean('enable_mpesa_stk')->default(false);
            $table->boolean('enable_mpesa_paybill')->default(true);
            $table->boolean('enable_bank_payment')->default(true);
            $table->boolean('enable_cash_payment')->default(true);
            $table->boolean('enable_cheque_payment')->default(false);

            $table->string('mpesa_paybill_number')->nullable();
            $table->string('mpesa_till_number')->nullable();
            $table->string('mpesa_account_name')->nullable();
            $table->string('mpesa_logo')->nullable();

            $table->text('invoice_payment_instructions')->nullable();
            $table->text('receipt_footer_note')->nullable();
            $table->text('invoice_footer_note')->nullable();

            $table->string('default_currency')->default('KES');
            $table->decimal('default_tax_rate', 8, 2)->default(0);
            $table->boolean('prices_include_tax')->default(false);

            $table->string('payment_stamp_image')->nullable();
            $table->string('authorized_signature_image')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
