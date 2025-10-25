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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Shipment Details
            $table->enum('status', ['draft', 'picked', 'packed', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->enum('shipment_type', ['full', 'partial'])->default('full');

            // Dates
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->date('expected_delivery_date')->nullable();

            // Shipping Details
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost', 15, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit')->default('kg');

            // Address
            $table->text('shipping_address');

            // Users
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();

            // Notes
            $table->text('notes')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('shipment_number');
            $table->index('sales_order_id');
            $table->index('status');
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('quantity', 15, 2);
            $table->string('location')->nullable()->comment('Warehouse location picked from');

            $table->timestamps();

            // Indexes
            $table->index('shipment_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};
