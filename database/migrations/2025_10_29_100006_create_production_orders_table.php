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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PRO-YYYYMMDD-####
            $table->foreignId('bill_of_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // What to produce
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete(); // Production location
            $table->decimal('quantity_to_produce', 15, 2);
            $table->decimal('quantity_produced', 15, 2)->default(0);
            $table->decimal('quantity_scrapped', 15, 2)->default(0);
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'draft',
                'planned',
                'released',
                'materials_reserved',
                'in_progress',
                'completed',
                'cancelled',
                'on_hold'
            ])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('material_allocation_mode', ['auto', 'manual'])->default('auto');
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete(); // Link to sales order if applicable
            $table->string('sales_order_reference')->nullable();
            $table->string('customer_reference')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->integer('estimated_time_minutes')->nullable();
            $table->integer('actual_time_minutes')->nullable();
            $table->text('production_notes')->nullable();
            $table->text('quality_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('attachments')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference');
            $table->index('status');
            $table->index('priority');
            $table->index('product_id');
            $table->index('bill_of_material_id');
            $table->index('sales_order_id');
            $table->index('planned_start_date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
