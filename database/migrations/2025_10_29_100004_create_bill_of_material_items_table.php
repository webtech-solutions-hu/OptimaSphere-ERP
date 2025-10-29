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
        Schema::create('bill_of_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Component/raw material
            $table->foreignId('parent_item_id')->nullable()->constrained('bill_of_material_items')->cascadeOnDelete(); // For multi-level BOM
            $table->integer('level')->default(1); // BOM level (1=top level, 2=sub-assembly, etc.)
            $table->integer('sequence')->default(0); // Order in the BOM
            $table->decimal('quantity', 15, 4)->default(1); // Quantity required
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0); // quantity * unit_cost
            $table->decimal('scrap_percentage', 5, 2)->default(0);
            $table->decimal('quantity_with_scrap', 15, 4)->default(0); // Including scrap
            $table->string('item_type')->default('component'); // component, raw_material, sub_assembly
            $table->string('reference_designator')->nullable(); // e.g., "R1", "C2" for electronics
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_phantom')->default(false); // Phantom items are not stocked
            $table->text('notes')->nullable();
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('bill_of_material_id');
            $table->index('product_id');
            $table->index('parent_item_id');
            $table->index(['bill_of_material_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_material_items');
    }
};
