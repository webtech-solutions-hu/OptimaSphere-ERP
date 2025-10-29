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
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // BOM-YYYYMMDD-####
            $table->string('name');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // The finished product
            $table->string('version')->default('1.0');
            $table->foreignId('parent_bom_id')->nullable()->constrained('bill_of_materials')->nullOnDelete(); // For versioning
            $table->boolean('is_active')->default(true);
            $table->boolean('is_latest_version')->default(true);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'obsolete'])->default('draft');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1); // Quantity this BOM produces
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_cost', 15, 2)->default(0); // Rolled-up material cost
            $table->decimal('labor_cost', 15, 2)->default(0);
            $table->decimal('overhead_cost', 15, 2)->default(0);
            $table->decimal('total_bom_cost', 15, 2)->default(0); // total_cost + labor + overhead
            $table->integer('estimated_time_minutes')->nullable(); // Production time
            $table->string('bom_type')->default('manufacturing'); // manufacturing, engineering, sales
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference');
            $table->index('product_id');
            $table->index('status');
            $table->index('is_active');
            $table->index('is_latest_version');
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_materials');
    }
};
