<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinishedGood extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'bill_of_material_id',
        'product_line',
        'model_number',
        'standard_production_time',
        'standard_cost',
        'quality_standard',
        'requires_testing',
        'testing_procedures',
        'packaging_type',
        'warranty_period_days',
        'assembly_notes',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'standard_production_time' => 'integer',
            'standard_cost' => 'decimal:2',
            'requires_testing' => 'boolean',
            'warranty_period_days' => 'integer',
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
     * Get the bill of material
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    /**
     * Check if testing is required
     */
    public function requiresTesting(): bool
    {
        return $this->requires_testing;
    }

    /**
     * Calculate production time for quantity
     */
    public function calculateProductionTime(float $quantity): ?int
    {
        if (!$this->standard_production_time) {
            return null;
        }

        return (int) ceil($this->standard_production_time * $quantity);
    }

    /**
     * Calculate standard cost for quantity
     */
    public function calculateStandardCost(float $quantity): float
    {
        if (!$this->standard_cost) {
            return 0;
        }

        return $this->standard_cost * $quantity;
    }

    /**
     * Get warranty expiry date
     */
    public function getWarrantyExpiryDate(?string $purchaseDate = null): ?string
    {
        if (!$this->warranty_period_days || !$purchaseDate) {
            return null;
        }

        return now()->parse($purchaseDate)->addDays($this->warranty_period_days)->toDateString();
    }

    /**
     * Check if warranty is valid
     */
    public function isWarrantyValid(?string $purchaseDate = null): bool
    {
        if (!$this->warranty_period_days || !$purchaseDate) {
            return false;
        }

        $expiryDate = now()->parse($purchaseDate)->addDays($this->warranty_period_days);
        return now()->lessThanOrEqualTo($expiryDate);
    }

    /**
     * Scope by product line
     */
    public function scopeProductLine($query, string $line)
    {
        return $query->where('product_line', $line);
    }

    /**
     * Scope for products requiring testing
     */
    public function scopeRequiresTesting($query)
    {
        return $query->where('requires_testing', true);
    }
}
