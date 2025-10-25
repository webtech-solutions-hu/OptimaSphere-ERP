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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('Adjustment reference number');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Adjustment Details
            $table->enum('type', ['increase', 'decrease'])->comment('Increase or decrease stock');
            $table->enum('reason', [
                'damaged',
                'expired',
                'lost',
                'found',
                'theft',
                'audit_correction',
                'quality_issue',
                'returned',
                'sample',
                'other'
            ])->comment('Reason for adjustment');

            $table->decimal('quantity', 15, 2)->comment('Quantity to adjust (always positive)');
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();

            // Balance tracking
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);

            // Approval Workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable()->comment('Supporting documents (photos, reports)');
            $table->timestamp('adjustment_date')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('reference');
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('reason');
            $table->index('adjustment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
