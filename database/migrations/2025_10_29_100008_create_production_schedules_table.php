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
        Schema::create('production_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // SCH-YYYYMMDD-####
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_center_id')->constrained()->cascadeOnDelete();
            $table->string('operation_name')->nullable();
            $table->integer('sequence')->default(1); // Order of operations
            $table->enum('status', ['scheduled', 'ready', 'in_progress', 'completed', 'cancelled', 'on_hold'])->default('scheduled');
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->integer('setup_time_minutes')->default(0);
            $table->integer('run_time_minutes')->default(0);
            $table->integer('teardown_time_minutes')->default(0);
            $table->decimal('quantity_scheduled', 15, 2);
            $table->decimal('quantity_completed', 15, 2)->default(0);
            $table->decimal('quantity_scrapped', 15, 2)->default(0);
            $table->foreignId('assigned_operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('has_conflict')->default(false); // Resource conflict
            $table->text('conflict_details')->nullable();
            $table->text('operation_notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference');
            $table->index('production_order_id');
            $table->index('work_center_id');
            $table->index('status');
            $table->index('scheduled_start');
            $table->index('has_conflict');
            $table->index(['work_center_id', 'scheduled_start', 'scheduled_end'], 'idx_wc_schedule_times');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_schedules');
    }
};
