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
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('component_type')->nullable(); // assembly, sub-assembly, part
            $table->boolean('is_assembly')->default(false);
            $table->boolean('is_interchangeable')->default(false);
            $table->string('drawing_number')->nullable();
            $table->string('revision')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->boolean('make_or_buy')->default(true); // true=make, false=buy
            $table->decimal('scrap_percentage', 5, 2)->default(0); // Expected waste
            $table->text('assembly_instructions')->nullable();
            $table->json('compatible_with')->nullable(); // Product IDs this component works with
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('component_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
