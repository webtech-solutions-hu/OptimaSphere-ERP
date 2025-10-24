<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Supplier reference code');

            // Type Classification
            $table->enum('type', ['manufacturer', 'distributor', 'service'])->default('manufacturer');

            // Company Information
            $table->string('company_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_title')->nullable();

            // Tax & Legal
            $table->string('tax_id')->unique()->nullable()->comment('VAT/Tax identification number');
            $table->string('registration_number')->nullable()->comment('Company registration number');

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Banking Information
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('bank_iban')->nullable();

            // Business Terms
            $table->integer('payment_terms')->default(30)->comment('Payment terms in days');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'check', 'other'])->default('bank_transfer');
            $table->decimal('credit_limit', 15, 2)->default(0)->comment('Maximum credit allowed');

            // Contract Details
            $table->string('contract_number')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->text('contract_terms')->nullable();

            // Categorization
            $table->string('category')->nullable()->comment('Product category or service type');
            $table->json('product_categories')->nullable()->comment('Multiple product categories');

            // Performance Tracking
            $table->decimal('performance_rating', 3, 2)->default(0)->comment('0-5 rating scale');
            $table->timestamp('last_transaction_date')->nullable();
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_purchase_amount', 15, 2)->default(0);

            // Assignment
            $table->foreignId('assigned_procurement_officer')->nullable()->constrained('users')->nullOnDelete();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false)->comment('Supplier approval status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('type');
            $table->index('email');
            $table->index('tax_id');
            $table->index('category');
            $table->index('is_active');
            $table->index('is_approved');
            $table->index('performance_rating');
            $table->index('last_transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
