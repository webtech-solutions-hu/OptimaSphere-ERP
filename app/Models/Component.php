<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'component_type',
        'is_assembly',
        'is_interchangeable',
        'drawing_number',
        'revision',
        'lead_time_days',
        'make_or_buy',
        'scrap_percentage',
        'assembly_instructions',
        'compatible_with',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'is_assembly' => 'boolean',
            'is_interchangeable' => 'boolean',
            'lead_time_days' => 'integer',
            'make_or_buy' => 'boolean',
            'scrap_percentage' => 'decimal:2',
            'compatible_with' => 'array',
            'custom_attributes' => 'array',
        ];
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if component is make (vs buy)
     */
    public function isMake(): bool
    {
        return $this->make_or_buy === true;
    }

    /**
     * Check if component is buy (vs make)
     */
    public function isBuy(): bool
    {
        return $this->make_or_buy === false;
    }

    /**
     * Get make or buy label
     */
    public function getMakeOrBuyLabelAttribute(): string
    {
        return $this->make_or_buy ? 'Make' : 'Buy';
    }

    /**
     * Calculate quantity with scrap
     */
    public function calculateQuantityWithScrap(float $baseQuantity): float
    {
        if ($this->scrap_percentage <= 0) {
            return $baseQuantity;
        }

        return $baseQuantity * (1 + ($this->scrap_percentage / 100));
    }

    /**
     * Get compatible products
     */
    public function getCompatibleProducts()
    {
        if (!$this->compatible_with || empty($this->compatible_with)) {
            return collect();
        }

        return Product::whereIn('id', $this->compatible_with)->get();
    }

    /**
     * Scope for assemblies
     */
    public function scopeAssemblies($query)
    {
        return $query->where('is_assembly', true);
    }

    /**
     * Scope for parts (non-assemblies)
     */
    public function scopeParts($query)
    {
        return $query->where('is_assembly', false);
    }

    /**
     * Scope for make components
     */
    public function scopeMake($query)
    {
        return $query->where('make_or_buy', true);
    }

    /**
     * Scope for buy components
     */
    public function scopeBuy($query)
    {
        return $query->where('make_or_buy', false);
    }

    /**
     * Scope by component type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('component_type', $type);
    }
}
