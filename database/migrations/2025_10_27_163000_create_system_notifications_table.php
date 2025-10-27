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
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('info'); // info, success, warning, danger
            $table->string('icon')->nullable();
            $table->string('color')->nullable();

            // Target settings
            $table->enum('target_type', ['global', 'role', 'user'])->default('global');
            $table->foreignId('target_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_role_id')->nullable()->constrained('roles')->nullOnDelete();

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['draft', 'pending', 'scheduled', 'sent'])->default('draft');

            // Actions (buttons in notification)
            $table->json('actions')->nullable();

            // Metadata
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('target_type');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
