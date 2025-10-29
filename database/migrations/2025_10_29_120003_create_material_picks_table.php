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
        Schema::create('material_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_requisition_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('quantity_picked', 15, 4);
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('location')->nullable(); // Specific bin location picked from
            $table->timestamp('picked_at');
            $table->foreignId('picked_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['picked', 'issued', 'returned'])->default('picked');
            $table->boolean('verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('material_requisition_item_id');
            $table->index('product_id');
            $table->index('batch_id');
            $table->index('batch_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_picks');
    }
};
