<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'sku',
        'barcode',
        'name',
        'slug',
        'description',
        'short_description',
        'category_id',
        'unit_id',
        'type',
        'tags',
        'base_price',
        'cost_price',
        'min_price',
        'max_price',
        'currency',
        'current_stock',
        'reorder_level',
        'reorder_quantity',
        'max_stock_level',
        'track_inventory',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'primary_supplier_id',
        'manufacturer',
        'manufacturer_part_number',
        'brand',
        'image',
        'images',
        'attachments',
        'is_taxable',
        'tax_rate',
        'tax_class',
        'hs_code',
        'is_featured',
        'is_new',
        'is_on_sale',
        'sale_price',
        'sale_start_date',
        'sale_end_date',
        'is_active',
        'is_available_for_purchase',
        'is_available_online',
        'available_from',
        'available_until',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'total_sales',
        'total_revenue',
        'view_count',
        'last_sold_at',
        'last_restocked_at',
        'notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'images' => 'array',
            'attachments' => 'array',
            'meta_keywords' => 'array',
            'custom_fields' => 'array',
            'base_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'reorder_quantity' => 'decimal:2',
            'max_stock_level' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'weight' => 'decimal:3',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_on_sale' => 'boolean',
            'is_active' => 'boolean',
            'is_available_for_purchase' => 'boolean',
            'is_available_online' => 'boolean',
            'track_inventory' => 'boolean',
            'total_sales' => 'integer',
            'view_count' => 'integer',
            'sale_start_date' => 'date',
            'sale_end_date' => 'date',
            'available_from' => 'date',
            'available_until' => 'date',
            'last_sold_at' => 'datetime',
            'last_restocked_at' => 'datetime',
        ];
    }

    /**
     * Boot method to auto-generate product code, SKU, and slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = self::generateProductCode();
            }
            if (empty($product->sku)) {
                $product->sku = self::generateSKU($product);
            }
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->barcode)) {
                $product->barcode = self::generateBarcode();
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Generate unique product code
     */
    private static function generateProductCode(): string
    {
        $prefix = 'PROD';
        $lastProduct = self::withTrashed()->latest('id')->first();
        $number = $lastProduct ? intval(substr($lastProduct->code, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate SKU based on category, type, and sequential number
     */
    private static function generateSKU($product): string
    {
        $categoryPrefix = 'GEN';
        if ($product->category_id) {
            $category = Category::find($product->category_id);
            if ($category) {
                $categoryPrefix = strtoupper(substr($category->code, 0, 3));
            }
        }

        $typePrefix = strtoupper(substr($product->type ?? 'physical', 0, 1));
        $lastProduct = self::withTrashed()->latest('id')->first();
        $number = $lastProduct ? $lastProduct->id + 1 : 1;

        return "{$categoryPrefix}-{$typePrefix}-" . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate barcode (EAN-13 format)
     */
    private static function generateBarcode(): string
    {
        $prefix = '200'; // Internal barcode prefix
        $lastProduct = self::withTrashed()->latest('id')->first();
        $number = $lastProduct ? $lastProduct->id + 1 : 1;
        $code = $prefix . str_pad($number, 9, '0', STR_PAD_LEFT);

        // Calculate EAN-13 check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($code[$i]) * (($i % 2 === 0) ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $code . $checkDigit;
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the primary supplier
     */
    public function primarySupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'primary_supplier_id');
    }

    /**
     * Get warehouse stock for this product
     */
    public function warehouseStock()
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    /**
     * Get stock movements for this product
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get stock adjustments for this product
     */
    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Get warehouse transfers for this product
     */
    public function warehouseTransfers()
    {
        return $this->hasMany(WarehouseTransfer::class);
    }

    /**
     * Get stock in a specific warehouse
     */
    public function getStockInWarehouse(int $warehouseId): ?ProductWarehouseStock
    {
        return $this->warehouseStock()->where('warehouse_id', $warehouseId)->first();
    }

    /**
     * Get total stock across all warehouses
     */
    public function getTotalStockAttribute(): float
    {
        return $this->warehouseStock()->sum('quantity');
    }

    /**
     * Get available stock across all warehouses
     */
    public function getAvailableStockAttribute(): float
    {
        return $this->warehouseStock()->sum('available_quantity');
    }

    /**
     * Get current selling price (considering sale price)
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->is_on_sale && $this->sale_price && $this->isSaleActive()) {
            return $this->sale_price;
        }
        return $this->base_price;
    }

    /**
     * Check if sale is currently active
     */
    public function isSaleActive(): bool
    {
        if (!$this->is_on_sale) {
            return false;
        }

        $now = now();
        $startOk = !$this->sale_start_date || $now->gte($this->sale_start_date);
        $endOk = !$this->sale_end_date || $now->lte($this->sale_end_date);

        return $startOk && $endOk;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->current_stock > 0;
    }

    /**
     * Check if stock is low (below reorder level)
     */
    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }
        return $this->current_stock <= $this->reorder_level && $this->current_stock > 0;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }
        return $this->current_stock <= 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'Not Tracked';
        }
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }
        if ($this->isLowStock()) {
            return 'Low Stock';
        }
        return 'In Stock';
    }

    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }
        return (($this->current_price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for new products
     */
    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    /**
     * Scope for products on sale
     */
    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true);
    }

    /**
     * Scope for in stock products
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
                ->orWhere('current_stock', '>', 0);
        });
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->where('current_stock', '>', 0);
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_inventory', true)
            ->where('current_stock', '<=', 0);
    }

    /**
     * Scope by category
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by tag
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
