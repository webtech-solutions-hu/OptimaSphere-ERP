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
        Schema::create('material_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // MR-YYYYMMDD-####
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['automatic', 'manual'])->default('manual');
            $table->enum('status', ['draft', 'submitted', 'approved', 'picking', 'issued', 'completed', 'cancelled'])->default('draft');
            $table->date('required_date')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference');
            $table->index('production_order_id');
            $table->index('warehouse_id');
            $table->index('status');
            $table->index('required_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requisitions');
    }
};
