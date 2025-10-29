<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'material_grade',
        'material_specification',
        'origin_country',
        'minimum_order_quantity',
        'storage_conditions',
        'shelf_life_days',
        'requires_quality_check',
        'handling_instructions',
        'certifications',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'minimum_order_quantity' => 'decimal:2',
            'shelf_life_days' => 'integer',
            'requires_quality_check' => 'boolean',
            'certifications' => 'array',
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
     * Check if quality check is required
     */
    public function requiresQualityCheck(): bool
    {
        return $this->requires_quality_check;
    }

    /**
     * Check if material has expired shelf life
     */
    public function isExpired(?string $receivedDate = null): bool
    {
        if (!$this->shelf_life_days || !$receivedDate) {
            return false;
        }

        $expiryDate = now()->parse($receivedDate)->addDays($this->shelf_life_days);
        return now()->greaterThan($expiryDate);
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(?string $receivedDate = null): ?int
    {
        if (!$this->shelf_life_days || !$receivedDate) {
            return null;
        }

        $expiryDate = now()->parse($receivedDate)->addDays($this->shelf_life_days);
        return now()->diffInDays($expiryDate, false);
    }

    /**
     * Scope for materials requiring quality check
     */
    public function scopeRequiresQualityCheck($query)
    {
        return $query->where('requires_quality_check', true);
    }

    /**
     * Scope by origin country
     */
    public function scopeFromCountry($query, string $country)
    {
        return $query->where('origin_country', $country);
    }
}
