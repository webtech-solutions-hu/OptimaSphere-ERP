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
        Schema::create('product_warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Stock levels
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('reserved_quantity', 15, 2)->default(0)->comment('Reserved for orders');
            $table->decimal('available_quantity', 15, 2)->default(0)->comment('Available for sale');

            // Location within warehouse
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin')->nullable();

            // Metadata
            $table->timestamp('last_counted_at')->nullable()->comment('Last physical count date');
            $table->foreignId('last_counted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Composite unique constraint
            $table->unique(['product_id', 'warehouse_id']);

            // Indexes
            $table->index('product_id');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_warehouse_stock');
    }
};
