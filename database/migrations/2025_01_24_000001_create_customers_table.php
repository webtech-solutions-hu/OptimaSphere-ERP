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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Customer reference code');
            $table->enum('type', ['b2b', 'b2c'])->default('b2b')->comment('Business type');

            // Company/Individual Information
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();

            // Tax & Legal
            $table->string('tax_id')->unique()->nullable()->comment('VAT/Tax identification number');
            $table->string('registration_number')->nullable()->comment('Company registration number');

            // Address
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Business Terms
            $table->integer('payment_terms')->default(30)->comment('Payment terms in days');
            $table->decimal('credit_limit', 15, 2)->default(0)->comment('Maximum credit allowed');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'check', 'other'])->default('bank_transfer');

            // Categorization
            $table->string('region')->nullable();
            $table->string('category')->nullable();
            $table->string('account_group')->nullable();

            // Relationships
            $table->foreignId('assigned_sales_rep')->nullable()->constrained('users')->nullOnDelete()->comment('Assigned sales representative');
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();

            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('type');
            $table->index('email');
            $table->index('tax_id');
            $table->index('is_active');
            $table->index('assigned_sales_rep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
