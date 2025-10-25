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
        // Add primary_category_id to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('primary_category_id')->nullable()->after('type')->constrained('categories')->nullOnDelete();
            $table->index('primary_category_id');
        });

        // Create supplier_category pivot table for many-to-many relationship
        Schema::create('supplier_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'category_id']);
            $table->index('supplier_id');
            $table->index('category_id');
        });

        // Add category_id to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('type')->constrained('categories')->nullOnDelete();
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['primary_category_id']);
            $table->dropColumn('primary_category_id');
        });

        Schema::dropIfExists('supplier_category');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
