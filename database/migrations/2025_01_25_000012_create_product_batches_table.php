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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Batch/Serial Information
            $table->string('batch_number')->nullable()->index();
            $table->string('serial_number')->nullable()->unique();
            $table->enum('tracking_type', ['batch', 'serial'])->default('batch');

            // Quantities (for batch tracking)
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('quantity_available', 15, 2)->default(0);
            $table->decimal('quantity_allocated', 15, 2)->default(0);

            // Status (for serial tracking)
            $table->enum('status', ['available', 'allocated', 'sold', 'returned', 'scrapped'])->default('available');

            // Dates
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_date')->nullable();

            // Source
            $table->string('source_document_type')->nullable()->comment('goods_receipt_note, stock_adjustment');
            $table->unsignedBigInteger('source_document_id')->nullable();

            // Location
            $table->string('storage_location')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin')->nullable();

            // Supplier
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            // Quality
            $table->enum('quality_status', ['passed', 'quarantine', 'failed', 'disposed'])->default('passed');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes (batch_number and serial_number already indexed above)
            $table->index('expiry_date');
            $table->index(['source_document_type', 'source_document_id']);
        });

        // Batch/Serial transaction history
        Schema::create('batch_serial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_batch_id')->constrained('product_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Transaction Details
            $table->enum('transaction_type', [
                'receipt',      // Initial receipt (GRN)
                'sale',         // Sold to customer
                'transfer_in',  // Transfer from another warehouse
                'transfer_out', // Transfer to another warehouse
                'adjustment',   // Manual adjustment
                'return',       // Customer/Supplier return
                'allocation',   // Reserved for order
                'release',      // Released from reservation
                'scrap',        // Scrapped/Disposed
            ]);

            $table->decimal('quantity', 15, 2)->nullable()->comment('For batch tracking');
            $table->decimal('quantity_before', 15, 2)->nullable();
            $table->decimal('quantity_after', 15, 2)->nullable();

            // Related Document
            $table->string('related_document_type')->nullable();
            $table->unsignedBigInteger('related_document_id')->nullable();

            // User & Date
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('transaction_date')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('product_batch_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index(['related_document_type', 'related_document_id'], 'bst_related_doc_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_serial_transactions');
        Schema::dropIfExists('product_batches');
    }
};
