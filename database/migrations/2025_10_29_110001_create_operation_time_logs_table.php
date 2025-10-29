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
        Schema::create('operation_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
            $table->enum('log_type', ['start', 'pause', 'resume', 'complete', 'scrap'])->default('start');
            $table->timestamp('logged_at');
            $table->decimal('quantity_processed', 15, 2)->nullable();
            $table->decimal('quantity_scrapped', 15, 2)->nullable();
            $table->string('scan_method')->nullable(); // barcode, qr_code, manual
            $table->string('scanned_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('production_order_operation_id');
            $table->index('operator_id');
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_time_logs');
    }
};
