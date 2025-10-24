<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'type',
        'base_unit_id',
        'conversion_factor',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conversion_factor' => 'decimal:6',
        ];
    }

    /**
     * Get the base unit for conversions
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get units that use this as base unit
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    /**
     * Get products using this unit
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Convert value to base unit
     */
    public function toBaseUnit(float $value): float
    {
        return $value * $this->conversion_factor;
    }

    /**
     * Convert value from base unit
     */
    public function fromBaseUnit(float $value): float
    {
        return $this->conversion_factor > 0 ? $value / $this->conversion_factor : 0;
    }

    /**
     * Convert to another unit
     */
    public function convertTo(float $value, Unit $targetUnit): float
    {
        // Convert to base unit first, then to target unit
        $baseValue = $this->toBaseUnit($value);
        return $targetUnit->fromBaseUnit($baseValue);
    }

    /**
     * Get display name with symbol
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->symbol ? "{$this->name} ({$this->symbol})" : $this->name;
    }

    /**
     * Scope for active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for base units (no parent)
     */
    public function scopeBase($query)
    {
        return $query->whereNull('base_unit_id');
    }
}
