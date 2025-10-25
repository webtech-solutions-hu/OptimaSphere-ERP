<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'manager_name',
        'phone',
        'email',
        'storage_capacity',
        'current_utilization',
        'is_active',
        'accepts_inbound',
        'accepts_outbound',
        'is_primary',
        'notes',
        'operating_hours',
    ];

    protected function casts(): array
    {
        return [
            'storage_capacity' => 'decimal:2',
            'current_utilization' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'accepts_inbound' => 'boolean',
            'accepts_outbound' => 'boolean',
            'is_primary' => 'boolean',
            'operating_hours' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate warehouse code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            if (empty($warehouse->code)) {
                $warehouse->code = self::generateWarehouseCode();
            }
        });
    }

    /**
     * Generate unique warehouse code
     */
    private static function generateWarehouseCode(): string
    {
        $prefix = 'WH';
        $lastWarehouse = self::withTrashed()->latest('id')->first();
        $number = $lastWarehouse ? intval(substr($lastWarehouse->code, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentageAttribute(): ?float
    {
        if (!$this->storage_capacity || $this->storage_capacity == 0) {
            return null;
        }

        return round(($this->current_utilization / $this->storage_capacity) * 100, 2);
    }

    /**
     * Stock movements
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Stock adjustments
     */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Transfers from this warehouse
     */
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'from_warehouse_id');
    }

    /**
     * Transfers to this warehouse
     */
    public function transfersTo(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'to_warehouse_id');
    }

    /**
     * Get product stock in this warehouse
     */
    public function productStock(): HasMany
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
