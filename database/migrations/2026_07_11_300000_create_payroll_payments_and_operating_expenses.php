<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_statutory_rates')) {
            Schema::create('payroll_statutory_rates', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name');
                $table->string('type', 40)->index();
                $table->date('effective_from')->index();
                $table->date('effective_to')->nullable()->index();
                $table->decimal('employee_rate', 10, 4)->nullable();
                $table->decimal('employer_rate', 10, 4)->nullable();
                $table->decimal('minimum_amount', 15, 2)->nullable();
                $table->decimal('maximum_amount', 15, 2)->nullable();
                $table->decimal('lower_earning_limit', 15, 2)->nullable();
                $table->decimal('upper_earning_limit', 15, 2)->nullable();
                $table->decimal('personal_relief', 15, 2)->nullable();
                $table->unsignedTinyInteger('remittance_due_day')->nullable();
                $table->json('bands')->nullable();
                $table->text('legal_reference')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'registered_pension_contribution')) {
                $table->decimal('registered_pension_contribution', 12, 2)
                    ->default(0)
                    ->after('housing_levy_enabled');
            }

            if (! Schema::hasColumn('employees', 'post_retirement_medical_contribution')) {
                $table->decimal('post_retirement_medical_contribution', 12, 2)
                    ->default(0)
                    ->after('registered_pension_contribution');
            }

            if (! Schema::hasColumn('employees', 'mortgage_interest')) {
                $table->decimal('mortgage_interest', 12, 2)
                    ->default(0)
                    ->after('post_retirement_medical_contribution');
            }

            if (! Schema::hasColumn('employees', 'insurance_relief')) {
                $table->decimal('insurance_relief', 12, 2)
                    ->default(0)
                    ->after('mortgage_interest');
            }
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $columns = [
                'total_gross' => ['decimal', 15, 2, 0],
                'total_paye' => ['decimal', 15, 2, 0],
                'total_nssf_employee' => ['decimal', 15, 2, 0],
                'total_nssf_employer' => ['decimal', 15, 2, 0],
                'total_shif' => ['decimal', 15, 2, 0],
                'total_housing_levy_employee' => ['decimal', 15, 2, 0],
                'total_housing_levy_employer' => ['decimal', 15, 2, 0],
                'total_salary_advance_deductions' => ['decimal', 15, 2, 0],
                'total_other_deductions' => ['decimal', 15, 2, 0],
                'total_net' => ['decimal', 15, 2, 0],
                'total_employer_cost' => ['decimal', 15, 2, 0],
                'total_paid' => ['decimal', 15, 2, 0],
                'balance_due' => ['decimal', 15, 2, 0],
            ];

            foreach ($columns as $name => [$type, $precision, $scale, $default]) {
                if (! Schema::hasColumn('payrolls', $name)) {
                    $table->{$type}($name, $precision, $scale)->default($default);
                }
            }

            if (! Schema::hasColumn('payrolls', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->index();
            }
        });

        Schema::table('payroll_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_items', 'employer_nssf')) {
                $table->decimal('employer_nssf', 12, 2)->default(0)->after('nssf');
            }

            if (! Schema::hasColumn('payroll_items', 'employer_housing_levy')) {
                $table->decimal('employer_housing_levy', 12, 2)
                    ->default(0)
                    ->after('housing_levy');
            }

            if (! Schema::hasColumn('payroll_items', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('net_pay');
            }

            if (! Schema::hasColumn('payroll_items', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->index();
            }
        });

        if (! Schema::hasTable('payroll_payments')) {
            Schema::create('payroll_payments', function (Blueprint $table): void {
                $table->id();
                $table->string('payment_number', 60)->unique();
                $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
                $table->dateTime('payment_date')->index();
                $table->string('status', 30)->default('draft')->index();
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['payroll_id', 'status']);
            });
        }

        if (! Schema::hasTable('payroll_payment_items')) {
            Schema::create('payroll_payment_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_payment_id')
                    ->constrained('payroll_payments')
                    ->cascadeOnDelete();
                $table->foreignId('payroll_item_id')
                    ->constrained('payroll_items')
                    ->restrictOnDelete();
                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method', 30)->default('bank');
                $table->string('phone_number', 30)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('transaction_reference', 120)->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(
                    ['payroll_payment_id', 'payroll_item_id'],
                    'ppi_payment_payroll_item_unique'
                );
            });
        }

        if (! Schema::hasTable('statutory_remittances')) {
            Schema::create('statutory_remittances', function (Blueprint $table): void {
                $table->id();
                $table->string('remittance_number', 60)->unique();
                $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
                $table->string('statutory_type', 40)->index();
                $table->date('period_start')->index();
                $table->date('period_end')->index();
                $table->date('due_date')->nullable()->index();
                $table->dateTime('payment_date')->nullable()->index();
                $table->decimal('amount_due', 15, 2)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('payment_method', 30)->default('bank');
                $table->string('payment_registration_number', 120)->nullable();
                $table->string('transaction_reference', 120)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->string('attachment_path')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['payroll_id', 'statutory_type', 'status'], 'sr_payroll_type_status_idx');
            });
        }

        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->foreignId('account_id')
                    ->constrained('accounting_accounts')
                    ->restrictOnDelete();
                $table->string('default_tax_treatment', 40)->default('non_vat');
                $table->string('default_wht_code', 50)->nullable();
                $table->decimal('default_wht_rate', 8, 4)->default(0);
                $table->boolean('requires_etims')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('operating_expenses')) {
            Schema::create('operating_expenses', function (Blueprint $table): void {
                $table->id();
                $table->string('expense_number', 60)->unique();
                $table->foreignId('expense_category_id')
                    ->constrained('expense_categories')
                    ->restrictOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->foreignId('cost_center_id')
                    ->nullable()
                    ->constrained('accounting_cost_centers')
                    ->nullOnDelete();
                $table->foreignId('project_fund_id')
                    ->nullable()
                    ->constrained('accounting_project_funds')
                    ->nullOnDelete();
                $table->date('expense_date')->index();
                $table->date('due_date')->nullable()->index();
                $table->string('supplier_invoice_number', 120)->nullable()->index();
                $table->string('etims_invoice_number', 120)->nullable()->index();
                $table->string('supplier_kra_pin', 30)->nullable()->index();
                $table->string('supplier_residency', 30)->default('resident');
                $table->string('tax_treatment', 40)->default('non_vat');
                $table->decimal('vat_rate', 8, 4)->default(0);
                $table->boolean('vat_claimable')->default(false);
                $table->string('withholding_tax_code', 50)->nullable();
                $table->decimal('withholding_tax_rate', 8, 4)->default(0);
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->decimal('vat_amount', 15, 2)->default(0);
                $table->decimal('withholding_tax_amount', 15, 2)->default(0);
                $table->decimal('gross_amount', 15, 2)->default(0);
                $table->decimal('payable_amount', 15, 2)->default(0);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('balance_due', 15, 2)->default(0);
                $table->string('status', 30)->default('draft')->index();
                $table->string('description');
                $table->string('receipt_path')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['expense_date', 'status']);
            });
        }

        if (! Schema::hasTable('operating_expense_payments')) {
            Schema::create('operating_expense_payments', function (Blueprint $table): void {
                $table->id();
                $table->string('payment_number', 60)->unique();
                $table->foreignId('operating_expense_id')
                    ->constrained('operating_expenses')
                    ->restrictOnDelete();
                $table->dateTime('payment_date')->index();
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 30)->default('bank');
                $table->string('transaction_reference', 120)->nullable();
                $table->string('mpesa_phone', 30)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['operating_expense_id', 'status'], 'oep_expense_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operating_expense_payments');
        Schema::dropIfExists('operating_expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('statutory_remittances');
        Schema::dropIfExists('payroll_payment_items');
        Schema::dropIfExists('payroll_payments');

        Schema::table('payroll_items', function (Blueprint $table): void {
            foreach ([
                'employer_nssf',
                'employer_housing_levy',
                'paid_amount',
                'payment_status',
            ] as $column) {
                if (Schema::hasColumn('payroll_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            foreach ([
                'total_gross',
                'total_paye',
                'total_nssf_employee',
                'total_nssf_employer',
                'total_shif',
                'total_housing_levy_employee',
                'total_housing_levy_employer',
                'total_salary_advance_deductions',
                'total_other_deductions',
                'total_net',
                'total_employer_cost',
                'total_paid',
                'balance_due',
                'payment_status',
            ] as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            foreach ([
                'registered_pension_contribution',
                'post_retirement_medical_contribution',
                'mortgage_interest',
                'insurance_relief',
            ] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('payroll_statutory_rates');
    }
};
