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
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_of_material_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Component/material needed
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete(); // Source warehouse
            $table->decimal('quantity_required', 15, 4);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('quantity_issued', 15, 4)->default(0);
            $table->decimal('quantity_consumed', 15, 4)->default(0);
            $table->decimal('quantity_returned', 15, 4)->default(0);
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->enum('status', ['pending', 'reserved', 'picked', 'issued', 'consumed', 'returned'])->default('pending');
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('production_order_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_items');
    }
};
