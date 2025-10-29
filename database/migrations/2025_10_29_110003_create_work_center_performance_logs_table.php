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
        Schema::create('work_center_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_center_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('availability_percentage', 5, 2)->default(100); // Uptime
            $table->decimal('performance_percentage', 5, 2)->default(100); // Speed efficiency
            $table->decimal('quality_percentage', 5, 2)->default(100); // Good parts
            $table->decimal('oee_percentage', 5, 2)->default(100); // Overall Equipment Effectiveness
            $table->integer('total_scheduled_minutes')->default(0);
            $table->integer('total_downtime_minutes')->default(0);
            $table->integer('total_productive_minutes')->default(0);
            $table->decimal('total_units_produced', 15, 2)->default(0);
            $table->decimal('total_units_scrapped', 15, 2)->default(0);
            $table->decimal('target_units', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['work_center_id', 'log_date']);
            $table->unique(['work_center_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_performance_logs');
    }
};
