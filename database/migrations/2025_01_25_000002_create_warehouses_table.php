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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Warehouse code (e.g., WH001)');
            $table->string('name');
            $table->enum('type', ['main', 'regional', 'distribution', 'storage', 'transit'])->default('main');

            // Location
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Contact
            $table->string('manager_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Capacity
            $table->decimal('storage_capacity', 15, 2)->nullable()->comment('Total storage capacity in cubic meters');
            $table->decimal('current_utilization', 15, 2)->default(0)->comment('Current utilization in cubic meters');

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_inbound')->default(true)->comment('Can receive stock');
            $table->boolean('accepts_outbound')->default(true)->comment('Can ship stock');
            $table->boolean('is_primary')->default(false)->comment('Primary/default warehouse');

            // Metadata
            $table->text('notes')->nullable();
            $table->json('operating_hours')->nullable()->comment('Store operating hours as JSON');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('type');
            $table->index('is_active');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
