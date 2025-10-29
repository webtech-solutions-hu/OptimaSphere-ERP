<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'bill_of_material_item_id',
        'product_id',
        'warehouse_id',
        'quantity_required',
        'quantity_reserved',
        'quantity_issued',
        'quantity_consumed',
        'quantity_returned',
        'unit_id',
        'unit_cost',
        'total_cost',
        'status',
        'batch_number',
        'serial_number',
        'picked_by',
        'picked_at',
        'issued_by',
        'issued_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'quantity_issued' => 'decimal:4',
            'quantity_consumed' => 'decimal:4',
            'quantity_returned' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'picked_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    /**
     * Get the production order
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the BOM item
     */
    public function billOfMaterialItem(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterialItem::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get picker user
     */
    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    /**
     * Get issuer user
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Reserve material from inventory
     */
    public function reserveMaterial(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        // Find warehouse with sufficient stock
        if (!$this->warehouse_id) {
            $this->warehouse_id = $this->findWarehouseWithStock();
        }

        if (!$this->warehouse_id) {
            return false; // No warehouse has sufficient stock
        }

        $stock = ProductWarehouseStock::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if (!$stock || $stock->available_quantity < $this->quantity_required) {
            return false;
        }

        // Reserve the stock
        $stock->reserved_quantity += $this->quantity_required;
        $stock->available_quantity -= $this->quantity_required;
        $stock->save();

        $this->quantity_reserved = $this->quantity_required;
        $this->status = 'reserved';

        return $this->save();
    }

    /**
     * Unreserve material
     */
    public function unreserveMaterial(): bool
    {
        if ($this->status !== 'reserved' || !$this->warehouse_id) {
            return false;
        }

        $stock = ProductWarehouseStock::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if (!$stock) {
            return false;
        }

        // Unreserve the stock
        $stock->reserved_quantity -= $this->quantity_reserved;
        $stock->available_quantity += $this->quantity_reserved;
        $stock->save();

        $this->quantity_reserved = 0;
        $this->status = 'pending';

        return $this->save();
    }

    /**
     * Pick material
     */
    public function pick(int $userId): bool
    {
        if ($this->status !== 'reserved') {
            return false;
        }

        $this->status = 'picked';
        $this->picked_by = $userId;
        $this->picked_at = now();

        return $this->save();
    }

    /**
     * Issue material to production
     */
    public function issue(int $userId): bool
    {
        if ($this->status !== 'picked') {
            return false;
        }

        // Create stock movement for consumption
        StockMovement::create([
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'type' => 'outbound',
            'quantity' => $this->quantity_required,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'related_document_type' => 'production_order',
            'related_document_id' => $this->production_order_id,
            'created_by' => $userId,
            'notes' => "Material issued for production order {$this->productionOrder->reference}",
            'movement_date' => now(),
        ]);

        // Update warehouse stock
        $stock = ProductWarehouseStock::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if ($stock) {
            $stock->quantity -= $this->quantity_required;
            $stock->reserved_quantity -= $this->quantity_required;
            $stock->save();
        }

        $this->quantity_issued = $this->quantity_required;
        $this->status = 'issued';
        $this->issued_by = $userId;
        $this->issued_at = now();

        return $this->save();
    }

    /**
     * Consume material
     */
    public function consume(float $quantity): bool
    {
        if ($this->status !== 'issued') {
            return false;
        }

        $this->quantity_consumed += $quantity;

        if ($this->quantity_consumed >= $this->quantity_issued) {
            $this->status = 'consumed';
        }

        return $this->save();
    }

    /**
     * Return unused material
     */
    public function returnMaterial(float $quantity): bool
    {
        if ($this->status !== 'issued') {
            return false;
        }

        if ($quantity > ($this->quantity_issued - $this->quantity_consumed)) {
            return false; // Cannot return more than issued minus consumed
        }

        // Create stock movement for return
        StockMovement::create([
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'type' => 'inbound',
            'quantity' => $quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $quantity * $this->unit_cost,
            'related_document_type' => 'production_order',
            'related_document_id' => $this->production_order_id,
            'notes' => "Material returned from production order {$this->productionOrder->reference}",
            'movement_date' => now(),
        ]);

        // Update warehouse stock
        $stock = ProductWarehouseStock::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if ($stock) {
            $stock->quantity += $quantity;
            $stock->available_quantity += $quantity;
            $stock->save();
        }

        $this->quantity_returned += $quantity;
        $this->status = 'returned';

        return $this->save();
    }

    /**
     * Find warehouse with sufficient stock
     */
    private function findWarehouseWithStock(): ?int
    {
        $stock = ProductWarehouseStock::where('product_id', $this->product_id)
            ->where('available_quantity', '>=', $this->quantity_required)
            ->orderBy('available_quantity', 'desc')
            ->first();

        return $stock?->warehouse_id;
    }

    /**
     * Get remaining quantity to issue
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity_required - $this->quantity_issued;
    }

    /**
     * Get remaining quantity to consume
     */
    public function getRemainingToConsumeAttribute(): float
    {
        return $this->quantity_issued - $this->quantity_consumed - $this->quantity_returned;
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending items
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for reserved items
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }
}
