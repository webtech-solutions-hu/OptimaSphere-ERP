<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWarehouseStock extends Model
{
    use HasFactory;

    protected $table = 'product_warehouse_stock';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'last_counted_at',
        'last_counted_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
            'available_quantity' => 'decimal:2',
            'last_counted_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lastCountedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_counted_by');
    }

    /**
     * Get location string
     */
    public function getLocationAttribute(): ?string
    {
        $parts = array_filter([
            $this->aisle ? "Aisle: {$this->aisle}" : null,
            $this->rack ? "Rack: {$this->rack}" : null,
            $this->shelf ? "Shelf: {$this->shelf}" : null,
            $this->bin ? "Bin: {$this->bin}" : null,
        ]);

        return $parts ? implode(' | ', $parts) : null;
    }

    /**
     * Update available quantity
     */
    public function updateAvailableQuantity(): void
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        $this->save();
    }

    /**
     * Add stock
     */
    public function addStock(float $quantity): void
    {
        $this->quantity += $quantity;
        $this->updateAvailableQuantity();
    }

    /**
     * Remove stock
     */
    public function removeStock(float $quantity): void
    {
        $this->quantity -= $quantity;
        $this->updateAvailableQuantity();
    }

    /**
     * Reserve stock
     */
    public function reserveStock(float $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->updateAvailableQuantity();

        return true;
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(float $quantity): void
    {
        $this->reserved_quantity -= $quantity;
        $this->updateAvailableQuantity();
    }
}
