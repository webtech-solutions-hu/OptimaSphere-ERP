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
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('material_grade')->nullable();
            $table->string('material_specification')->nullable();
            $table->string('origin_country')->nullable();
            $table->decimal('minimum_order_quantity', 15, 2)->nullable();
            $table->string('storage_conditions')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->boolean('requires_quality_check')->default(false);
            $table->text('handling_instructions')->nullable();
            $table->json('certifications')->nullable(); // ISO, food-grade, etc.
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
