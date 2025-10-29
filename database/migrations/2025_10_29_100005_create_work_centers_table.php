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
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // WC-####
            $table->string('name');
            $table->string('type'); // machine, manual, assembly, quality_control, packaging
            $table->text('description')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete(); // Physical location
            $table->string('location_details')->nullable(); // Floor, bay, line, etc.
            $table->decimal('capacity_per_day', 10, 2)->default(8); // Hours or units
            $table->string('capacity_unit')->default('hours'); // hours, units, shifts
            $table->decimal('efficiency_percentage', 5, 2)->default(100);
            $table->decimal('utilization_percentage', 5, 2)->default(0); // Current utilization
            $table->decimal('cost_per_hour', 10, 2)->default(0);
            $table->integer('setup_time_minutes')->default(0);
            $table->integer('teardown_time_minutes')->default(0);
            $table->integer('minimum_batch_size')->default(1);
            $table->integer('maximum_batch_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->boolean('requires_operator')->default(true);
            $table->integer('required_operators')->default(1);
            $table->json('operating_hours')->nullable(); // Schedule per day
            $table->json('capabilities')->nullable(); // What operations this center can perform
            $table->json('certifications')->nullable();
            $table->date('maintenance_due_date')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('type');
            $table->index('is_active');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_centers');
    }
};
