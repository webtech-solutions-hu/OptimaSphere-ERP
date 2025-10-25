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
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('Transfer reference number');

            // Warehouses
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Product & Quantity
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();

            // Workflow
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'in_transit',
                'received',
                'rejected',
                'cancelled'
            ])->default('draft');

            // Dates
            $table->timestamp('requested_date')->useCurrent();
            $table->timestamp('approved_date')->nullable();
            $table->timestamp('shipped_date')->nullable();
            $table->timestamp('received_date')->nullable();
            $table->date('expected_delivery_date')->nullable();

            // Users
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

            // Shipping Details
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 15, 2)->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('attachments')->nullable()->comment('Packing slips, delivery receipts');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('reference');
            $table->index('from_warehouse_id');
            $table->index('to_warehouse_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('requested_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
