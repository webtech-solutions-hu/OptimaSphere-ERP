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
        // Drop tables if they exist from failed migration
        Schema::dropIfExists('batch_serial_transactions');
        Schema::dropIfExists('product_batches');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do on rollback
    }
};
