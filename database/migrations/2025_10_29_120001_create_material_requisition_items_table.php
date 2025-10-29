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
        Schema::create('material_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_requested', 15, 4);
            $table->decimal('quantity_approved', 15, 4)->default(0);
            $table->decimal('quantity_picked', 15, 4)->default(0);
            $table->decimal('quantity_issued', 15, 4)->default(0);
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'picking', 'picked', 'issued', 'short'])->default('pending');
            $table->string('storage_location')->nullable(); // aisle-rack-shelf-bin
            $table->boolean('requires_batch_tracking')->default(false);
            $table->text('picking_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('material_requisition_id');
            $table->index('production_order_item_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requisition_items');
    }
};
