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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Unit reference code (e.g., KG, L, PCS)');
            $table->string('name')->comment('Unit name (e.g., Kilogram, Liter, Pieces)');
            $table->string('symbol')->nullable()->comment('Unit symbol');
            $table->enum('type', ['weight', 'volume', 'length', 'area', 'quantity', 'time', 'other'])->default('quantity');
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->nullOnDelete()->comment('Reference to base unit for conversions');
            $table->decimal('conversion_factor', 15, 6)->default(1)->comment('Conversion factor to base unit');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
