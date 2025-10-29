<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterialItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bill_of_material_id',
        'product_id',
        'parent_item_id',
        'level',
        'sequence',
        'quantity',
        'unit_id',
        'unit_cost',
        'total_cost',
        'scrap_percentage',
        'quantity_with_scrap',
        'item_type',
        'reference_designator',
        'is_optional',
        'is_phantom',
        'notes',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sequence' => 'integer',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'scrap_percentage' => 'decimal:2',
            'quantity_with_scrap' => 'decimal:4',
            'is_optional' => 'boolean',
            'is_phantom' => 'boolean',
            'custom_attributes' => 'array',
        ];
    }

    /**
     * Boot method to calculate costs and quantities
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculate quantity with scrap
            if ($item->scrap_percentage > 0) {
                $item->quantity_with_scrap = $item->quantity * (1 + ($item->scrap_percentage / 100));
            } else {
                $item->quantity_with_scrap = $item->quantity;
            }

            // Calculate total cost
            $item->total_cost = $item->quantity * $item->unit_cost;
        });

        static::saved(function ($item) {
            // Recalculate parent BOM costs
            if ($item->billOfMaterial) {
                $item->billOfMaterial->recalculateCosts();
            }
        });

        static::deleted(function ($item) {
            // Recalculate parent BOM costs
            if ($item->billOfMaterial) {
                $item->billOfMaterial->recalculateCosts();
            }
        });
    }

    /**
     * Get the bill of material
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    /**
     * Get the product (component/material)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get parent item (for multi-level BOM)
     */
    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterialItem::class, 'parent_item_id');
    }

    /**
     * Get child items
     */
    public function childItems(): HasMany
    {
        return $this->hasMany(BillOfMaterialItem::class, 'parent_item_id')->orderBy('sequence');
    }

    /**
     * Calculate required quantity for production
     */
    public function calculateRequiredQuantity(float $productionQuantity): float
    {
        return $this->quantity_with_scrap * $productionQuantity;
    }

    /**
     * Calculate cost for production quantity
     */
    public function calculateCostForQuantity(float $productionQuantity): float
    {
        return $this->calculateRequiredQuantity($productionQuantity) * $this->unit_cost;
    }

    /**
     * Check if item is top-level
     */
    public function isTopLevel(): bool
    {
        return $this->level === 1;
    }

    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        return $this->childItems()->exists();
    }

    /**
     * Get all descendant items (recursive)
     */
    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->childItems as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get item depth in BOM hierarchy
     */
    public function getDepth(): int
    {
        $depth = 0;
        $item = $this;

        while ($item->parent_item_id) {
            $depth++;
            $item = $item->parentItem;
        }

        return $depth;
    }

    /**
     * Scope for top-level items
     */
    public function scopeTopLevel($query)
    {
        return $query->where('level', 1);
    }

    /**
     * Scope by level
     */
    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope by item type
     */
    public function scopeItemType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope for optional items
     */
    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    /**
     * Scope for required items
     */
    public function scopeRequired($query)
    {
        return $query->where('is_optional', false);
    }

    /**
     * Scope for phantom items
     */
    public function scopePhantom($query)
    {
        return $query->where('is_phantom', true);
    }

    /**
     * Scope for non-phantom items
     */
    public function scopeNonPhantom($query)
    {
        return $query->where('is_phantom', false);
    }
}
