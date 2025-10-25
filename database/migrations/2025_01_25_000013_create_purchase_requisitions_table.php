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
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // Type
            $table->enum('requisition_type', ['manual', 'auto_reorder', 'emergency'])->default('manual');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Status
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'converted_to_po',
                'cancelled'
            ])->default('draft');

            // Dates
            $table->date('requisition_date')->default(now());
            $table->date('required_by_date')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Users
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Conversion
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            // Notes
            $table->text('purpose')->nullable();
            $table->text('justification')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('pr_number');
            $table->index('status');
            $table->index('requisition_type');
            $table->index('requisition_date');
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('suggested_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            // Quantities
            $table->decimal('quantity_requested', 15, 2);
            $table->decimal('current_stock', 15, 2)->nullable()->comment('Stock level at time of request');
            $table->decimal('reorder_level', 15, 2)->nullable()->comment('Reorder level at time of request');

            // Pricing (estimates)
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_total', 15, 2)->nullable();

            $table->text('specification')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('purchase_requisition_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};
