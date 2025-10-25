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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('Movement reference number');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Movement Details
            $table->enum('type', [
                'in',           // Stock In
                'out',          // Stock Out
                'adjustment',   // Manual Adjustment
                'transfer_in',  // Transfer from another warehouse
                'transfer_out', // Transfer to another warehouse
                'return',       // Return from customer
                'damage',       // Damaged goods
                'lost',         // Lost inventory
                'found',        // Found inventory
            ])->comment('Type of stock movement');

            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('Cost per unit at time of movement');
            $table->decimal('total_cost', 15, 2)->nullable()->comment('Total cost of movement');

            // Balance tracking
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();

            // Related documents
            $table->string('related_document_type')->nullable()->comment('e.g., purchase_order, sale_order, transfer');
            $table->unsignedBigInteger('related_document_id')->nullable();

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('movement_date')->useCurrent();

            $table->timestamps();

            // Indexes
            $table->index('reference');
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('type');
            $table->index('movement_date');
            $table->index(['related_document_type', 'related_document_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
