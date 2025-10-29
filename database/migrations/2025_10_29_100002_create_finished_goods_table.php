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
        Schema::create('finished_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('bill_of_material_id')->nullable();
            $table->string('product_line')->nullable();
            $table->string('model_number')->nullable();
            $table->integer('standard_production_time')->nullable(); // in minutes
            $table->decimal('standard_cost', 15, 2)->nullable();
            $table->string('quality_standard')->nullable();
            $table->boolean('requires_testing')->default(false);
            $table->text('testing_procedures')->nullable();
            $table->string('packaging_type')->nullable();
            $table->integer('warranty_period_days')->nullable();
            $table->text('assembly_notes')->nullable();
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('bill_of_material_id');
            $table->index('product_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finished_goods');
    }
};
