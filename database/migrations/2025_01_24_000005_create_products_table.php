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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Product reference code');
            $table->string('sku')->unique()->comment('Stock Keeping Unit');
            $table->string('barcode')->unique()->nullable()->comment('Product barcode');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            // Classification
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->enum('type', ['physical', 'service', 'digital'])->default('physical');
            $table->json('tags')->nullable()->comment('Product tags: featured, new, seasonal, etc.');

            // Pricing
            $table->decimal('base_price', 15, 2)->default(0)->comment('Base selling price');
            $table->decimal('cost_price', 15, 2)->default(0)->comment('Cost/purchase price');
            $table->decimal('min_price', 15, 2)->nullable()->comment('Minimum selling price');
            $table->decimal('max_price', 15, 2)->nullable()->comment('Maximum selling price');
            $table->string('currency', 3)->default('USD');

            // Inventory
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 2)->default(0)->comment('Minimum stock level before reorder');
            $table->decimal('reorder_quantity', 15, 2)->default(0)->comment('Default quantity to reorder');
            $table->decimal('max_stock_level', 15, 2)->nullable()->comment('Maximum stock capacity');
            $table->boolean('track_inventory')->default(true);

            // Physical Attributes
            $table->decimal('weight', 10, 3)->nullable()->comment('Product weight');
            $table->string('weight_unit', 10)->nullable()->default('kg');
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit', 10)->nullable()->default('cm');

            // Supplier & Manufacturer
            $table->foreignId('primary_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('manufacturer_part_number')->nullable();
            $table->string('brand')->nullable();

            // Media
            $table->string('image')->nullable()->comment('Primary product image');
            $table->json('images')->nullable()->comment('Additional product images');
            $table->json('attachments')->nullable()->comment('Product documents, manuals, etc.');

            // Tax & Compliance
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_class')->nullable();
            $table->string('hs_code')->nullable()->comment('Harmonized System code for customs');

            // Sales & Marketing
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_on_sale')->default(false);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->date('sale_start_date')->nullable();
            $table->date('sale_end_date')->nullable();

            // Status & Visibility
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available_for_purchase')->default(true);
            $table->boolean('is_available_online')->default(true);
            $table->date('available_from')->nullable();
            $table->date('available_until')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Tracking
            $table->integer('total_sales')->default(0)->comment('Total units sold');
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_sold_at')->nullable();
            $table->timestamp('last_restocked_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('sku');
            $table->index('barcode');
            $table->index('slug');
            $table->index('category_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('is_on_sale');
            $table->index('current_stock');
            $table->index('reorder_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
