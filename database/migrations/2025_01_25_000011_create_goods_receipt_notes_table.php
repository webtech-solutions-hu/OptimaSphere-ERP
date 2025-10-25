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
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            // GRN Details
            $table->enum('status', [
                'draft',
                'verified',
                'approved',
                'discrepancy',
                'rejected',
                'completed'
            ])->default('draft');

            $table->enum('receipt_type', ['full', 'partial'])->default('full');

            $table->date('receipt_date')->default(now());
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Supplier Documents
            $table->string('supplier_invoice_number')->nullable();
            $table->string('supplier_delivery_note')->nullable();
            $table->date('supplier_invoice_date')->nullable();

            // Verification
            $table->boolean('has_discrepancy')->default(false);
            $table->text('discrepancy_notes')->nullable();
            $table->json('discrepancy_details')->nullable()->comment('Item-wise discrepancies');

            // Quality Check
            $table->enum('quality_status', ['passed', 'failed', 'partial', 'pending'])->default('pending');
            $table->text('quality_notes')->nullable();

            // Users
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Notes
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable()->comment('Photos, documents');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('grn_number');
            $table->index('purchase_order_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('receipt_date');
        });

        Schema::create('goods_receipt_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Quantities
            $table->decimal('quantity_ordered', 15, 2)->comment('From PO');
            $table->decimal('quantity_received', 15, 2);
            $table->decimal('quantity_accepted', 15, 2)->default(0);
            $table->decimal('quantity_rejected', 15, 2)->default(0);

            // Discrepancy
            $table->decimal('quantity_discrepancy', 15, 2)->default(0)->comment('Ordered - Received');
            $table->enum('discrepancy_type', ['shortage', 'overage', 'match', 'damage'])->default('match');
            $table->text('discrepancy_reason')->nullable();

            // Serial/Batch Tracking
            $table->string('batch_number')->nullable();
            $table->json('serial_numbers')->nullable()->comment('Array of serial numbers');
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Quality
            $table->enum('condition', ['good', 'damaged', 'defective', 'expired'])->default('good');

            // Location
            $table->string('storage_location')->nullable()->comment('Warehouse location stored');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('grn_id');
            $table->index('product_id');
            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_items');
        Schema::dropIfExists('goods_receipt_notes');
    }
};
