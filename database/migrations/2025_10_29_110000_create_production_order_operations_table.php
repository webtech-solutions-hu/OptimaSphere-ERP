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
        Schema::create('production_order_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_center_id')->constrained()->cascadeOnDelete();
            $table->string('operation_name');
            $table->integer('sequence')->default(1);
            $table->enum('status', ['pending', 'ready', 'in_progress', 'completed', 'paused', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->integer('total_pause_minutes')->default(0);
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->decimal('quantity_to_process', 15, 2);
            $table->decimal('quantity_completed', 15, 2)->default(0);
            $table->decimal('quantity_scrapped', 15, 2)->default(0);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('barcode')->nullable()->unique(); // For scanning
            $table->string('qr_code')->nullable(); // For scanning
            $table->text('operation_notes')->nullable();
            $table->text('quality_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('production_order_id');
            $table->index('work_center_id');
            $table->index('status');
            $table->index('operator_id');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_operations');
    }
};
