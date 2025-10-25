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
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();

            // Return Type
            $table->enum('return_type', ['customer_return', 'supplier_return'])->comment('Customer return or return to supplier');

            // Related Document (polymorphic)
            $table->string('original_document_type')->comment('sales_order, purchase_order, shipment');
            $table->unsignedBigInteger('original_document_id');

            // Customer or Supplier
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Status and Workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'received',
                'inspected',
                'approved',
                'rejected',
                'restocked',
                'disposed',
                'completed'
            ])->default('draft');

            // Reason
            $table->enum('reason', [
                'defective',
                'damaged',
                'wrong_item',
                'not_as_described',
                'customer_changed_mind',
                'quality_issue',
                'expired',
                'overstocked',
                'other'
            ]);

            // Dates
            $table->date('return_date')->default(now());
            $table->timestamp('received_at')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('restocked_at')->nullable();

            // Users
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Resolution
            $table->enum('resolution_action', ['credit', 'replacement', 'refund', 'repair', 'dispose'])->nullable();
            $table->enum('disposition', ['restock', 'scrap', 'repair', 'return_to_supplier'])->nullable();

            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->decimal('restocking_fee', 15, 2)->nullable();

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->json('attachments')->nullable()->comment('Photos, inspection reports');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('return_number');
            $table->index(['original_document_type', 'original_document_id']);
            $table->index('status');
            $table->index('return_type');
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('quantity_requested', 15, 2);
            $table->decimal('quantity_received', 15, 2)->default(0);
            $table->decimal('quantity_approved', 15, 2)->default(0);
            $table->decimal('quantity_restocked', 15, 2)->default(0);

            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();

            $table->enum('condition', ['new', 'good', 'damaged', 'defective', 'unusable'])->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('return_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};
