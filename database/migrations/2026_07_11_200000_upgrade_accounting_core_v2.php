<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_accounts', 'reporting_group')) {
                $table->string('reporting_group', 80)->nullable()->after('type')->index('aa_reporting_group_idx');
            }

            if (! Schema::hasColumn('accounting_accounts', 'tax_code')) {
                $table->string('tax_code', 50)->nullable()->after('reporting_group')->index('aa_tax_code_idx');
            }

            if (! Schema::hasColumn('accounting_accounts', 'is_control_account')) {
                $table->boolean('is_control_account')->default(false)->after('is_system');
            }

            if (! Schema::hasColumn('accounting_accounts', 'allow_manual_posting')) {
                $table->boolean('allow_manual_posting')->default(true)->after('is_control_account');
            }

            if (! Schema::hasColumn('accounting_accounts', 'requires_cost_center')) {
                $table->boolean('requires_cost_center')->default(false)->after('allow_manual_posting');
            }

            if (! Schema::hasColumn('accounting_accounts', 'requires_project')) {
                $table->boolean('requires_project')->default(false)->after('requires_cost_center');
            }
        });

        Schema::table('accounting_account_mappings', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_account_mappings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_required')->index('aam_active_idx');
            }
        });

        Schema::table('accounting_journal_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_journal_entries', 'posting_key')) {
                $table->string('posting_key', 190)->nullable()->after('journal_number')->unique('aje_posting_key_uq');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'source_reference')) {
                $table->string('source_reference', 190)->nullable()->after('source_id')->index('aje_source_ref_idx');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'currency_code')) {
                $table->char('currency_code', 3)->default('KES')->after('narration');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency_code');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('reversed_at');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'lock_version')) {
                $table->unsignedInteger('lock_version')->default(0)->after('metadata');
            }

            if (! Schema::hasColumn('accounting_journal_entries', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('accounting_journal_entry_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_journal_entry_lines', 'tax_code')) {
                $table->string('tax_code', 50)->nullable()->after('description')->index('ajel_tax_code_idx');
            }

            if (! Schema::hasColumn('accounting_journal_entry_lines', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 4)->nullable()->after('tax_code');
            }

            if (! Schema::hasColumn('accounting_journal_entry_lines', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            }

            if (! Schema::hasColumn('accounting_journal_entry_lines', 'party_pin')) {
                $table->string('party_pin', 30)->nullable()->after('party_id')->index('ajel_party_pin_idx');
            }

            if (! Schema::hasColumn('accounting_journal_entry_lines', 'party_name')) {
                $table->string('party_name')->nullable()->after('party_pin');
            }

            if (! Schema::hasColumn('accounting_journal_entry_lines', 'etims_document_number')) {
                $table->string('etims_document_number', 100)->nullable()->after('party_name')->index('ajel_etims_doc_idx');
            }
        });

        Schema::table('accounting_tax_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_tax_settings', 'resident_rate')) {
                $table->decimal('resident_rate', 8, 4)->nullable()->after('rate');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'non_resident_rate')) {
                $table->decimal('non_resident_rate', 8, 4)->nullable()->after('resident_rate');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'tax_scope')) {
                $table->string('tax_scope', 80)->nullable()->after('type')->index('ats_scope_idx');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'filing_frequency')) {
                $table->string('filing_frequency', 30)->nullable()->after('fixed_amount');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'return_due_day')) {
                $table->unsignedTinyInteger('return_due_day')->nullable()->after('filing_frequency');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'remittance_due_days')) {
                $table->unsignedTinyInteger('remittance_due_days')->nullable()->after('return_due_day');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'kra_return_code')) {
                $table->string('kra_return_code', 50)->nullable()->after('remittance_due_days');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'legal_reference')) {
                $table->text('legal_reference')->nullable()->after('kra_return_code');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'requires_etims')) {
                $table->boolean('requires_etims')->default(false)->after('legal_reference');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('accounting_tax_settings', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_system');
            }
        });

        Schema::table('accounting_opening_balances', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_opening_balances', 'status')) {
                $table->string('status', 20)->default('draft')->after('credit')->index('aob_status_idx');
            }

            if (! Schema::hasColumn('accounting_opening_balances', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->after('status')->constrained('accounting_journal_entries')->nullOnDelete();
            }

            if (! Schema::hasColumn('accounting_opening_balances', 'posted_by')) {
                $table->foreignId('posted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('accounting_opening_balances', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('posted_by');
            }
        });

        Schema::table('accounting_reconciliations', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_reconciliations', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('statement_date');
            }

            if (! Schema::hasColumn('accounting_reconciliations', 'closing_balance')) {
                $table->decimal('closing_balance', 15, 2)->default(0)->after('system_balance');
            }

            if (! Schema::hasColumn('accounting_reconciliations', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('accounting_reconciliations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('accounting_reconciliations', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('reconciled_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('accounting_reconciliations', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('completed_by');
            }
        });
    }

    public function down(): void
    {
        // Deliberately non-destructive. This migration upgrades a live ledger.
    }
};
