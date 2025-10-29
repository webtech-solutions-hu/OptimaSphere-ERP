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
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // MC-YYYYMMDD-####
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_order_operation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_consumed', 15, 4);
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->enum('consumption_type', ['standard', 'backflush', 'manual'])->default('standard');
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamp('consumed_at');
            $table->foreignId('consumed_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference');
            $table->index('production_order_id');
            $table->index('production_order_operation_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('batch_id');
            $table->index('batch_number');
            $table->index('consumed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
    }
};
