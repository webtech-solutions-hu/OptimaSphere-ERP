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
        Schema::create('work_center_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // MNT-YYYYMMDD-####
            $table->foreignId('work_center_id')->constrained()->cascadeOnDelete();
            $table->enum('maintenance_type', ['preventive', 'corrective', 'predictive', 'emergency'])->default('preventive');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->date('scheduled_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->text('description');
            $table->text('work_performed')->nullable();
            $table->text('parts_used')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->boolean('affects_production')->default(true);
            $table->date('next_maintenance_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference');
            $table->index('work_center_id');
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('maintenance_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_maintenances');
    }
};
