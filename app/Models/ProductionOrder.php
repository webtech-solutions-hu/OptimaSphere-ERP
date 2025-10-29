<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'bill_of_material_id',
        'product_id',
        'warehouse_id',
        'quantity_to_produce',
        'quantity_produced',
        'quantity_scrapped',
        'unit_id',
        'status',
        'priority',
        'material_allocation_mode',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'sales_order_id',
        'sales_order_reference',
        'customer_reference',
        'created_by',
        'assigned_to',
        'approved_by',
        'approved_at',
        'estimated_cost',
        'actual_cost',
        'estimated_time_minutes',
        'actual_time_minutes',
        'production_notes',
        'quality_notes',
        'cancellation_reason',
        'attachments',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'quantity_to_produce' => 'decimal:2',
            'quantity_produced' => 'decimal:2',
            'quantity_scrapped' => 'decimal:2',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_start_date' => 'datetime',
            'actual_end_date' => 'datetime',
            'approved_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'estimated_time_minutes' => 'integer',
            'actual_time_minutes' => 'integer',
            'attachments' => 'array',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->reference)) {
                $order->reference = self::generateReference();
            }

            // Set product_id from BOM if not already set
            if ($order->bill_of_material_id && empty($order->product_id)) {
                $bom = \App\Models\BillOfMaterial::find($order->bill_of_material_id);
                if ($bom) {
                    $order->product_id = $bom->product_id;
                }
            }

            // Set estimated cost from BOM
            if ($order->billOfMaterial && !$order->estimated_cost) {
                $order->estimated_cost = $order->billOfMaterial->total_bom_cost * $order->quantity_to_produce;
            }

            // Set estimated time from BOM
            if ($order->billOfMaterial && !$order->estimated_time_minutes) {
                $order->estimated_time_minutes = $order->billOfMaterial->estimated_time_minutes * $order->quantity_to_produce;
            }
        });

        static::created(function ($order) {
            // Create production order items from BOM
            if ($order->billOfMaterial) {
                $order->createItemsFromBOM();
            }
        });
    }

    /**
     * Generate unique reference
     */
    private static function generateReference(): string
    {
        $prefix = 'PRO';
        $date = now()->format('Ymd');

        $lastOrder = self::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->latest('id')
            ->first();

        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return "{$prefix}-{$date}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the bill of material
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
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
     * Get the sales order
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get assigned user
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get production order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    /**
     * Get production schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ProductionSchedule::class);
    }

    /**
     * Create production order items from BOM
     */
    public function createItemsFromBOM(): void
    {
        if (!$this->billOfMaterial) {
            return;
        }

        foreach ($this->billOfMaterial->items as $bomItem) {
            ProductionOrderItem::create([
                'production_order_id' => $this->id,
                'bill_of_material_item_id' => $bomItem->id,
                'product_id' => $bomItem->product_id,
                'quantity_required' => $bomItem->calculateRequiredQuantity($this->quantity_to_produce),
                'unit_id' => $bomItem->unit_id,
                'unit_cost' => $bomItem->unit_cost,
                'total_cost' => $bomItem->calculateCostForQuantity($this->quantity_to_produce),
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Reserve materials
     */
    public function reserveMaterials(): bool
    {
        if ($this->status !== 'released') {
            return false;
        }

        foreach ($this->items as $item) {
            if (!$item->reserveMaterial()) {
                // Rollback all reservations if any fails
                $this->unreserveMaterials();
                return false;
            }
        }

        $this->status = 'materials_reserved';
        return $this->save();
    }

    /**
     * Unreserve materials
     */
    public function unreserveMaterials(): void
    {
        foreach ($this->items as $item) {
            $item->unreserveMaterial();
        }
    }

    /**
     * Start production
     */
    public function start(int $userId): bool
    {
        if (!in_array($this->status, ['released', 'materials_reserved'])) {
            return false;
        }

        $this->status = 'in_progress';
        $this->actual_start_date = now();
        $this->assigned_to = $userId;

        return $this->save();
    }

    /**
     * Complete production
     */
    public function complete(int $userId): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'completed';
        $this->actual_end_date = now();

        // Calculate actual time
        if ($this->actual_start_date) {
            $this->actual_time_minutes = $this->actual_start_date->diffInMinutes($this->actual_end_date);
        }

        // Add finished goods to inventory
        $this->addFinishedGoodsToInventory();

        return $this->save();
    }

    /**
     * Cancel production order
     */
    public function cancel(int $userId, string $reason): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        // Unreserve materials if reserved
        if ($this->status === 'materials_reserved') {
            $this->unreserveMaterials();
        }

        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;

        return $this->save();
    }

    /**
     * Add finished goods to inventory
     */
    public function addFinishedGoodsToInventory(): void
    {
        if (!$this->warehouse || $this->quantity_produced <= 0) {
            return;
        }

        // Calculate unit cost (avoid division by zero)
        $unitCost = $this->quantity_produced > 0
            ? $this->actual_cost / $this->quantity_produced
            : 0;

        // Create stock movement for finished goods
        StockMovement::create([
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'type' => 'inbound',
            'quantity' => $this->quantity_produced,
            'unit_cost' => $unitCost,
            'total_cost' => $this->actual_cost,
            'related_document_type' => 'production_order',
            'related_document_id' => $this->id,
            'created_by' => $this->assigned_to,
            'notes' => "Finished goods from production order {$this->reference}",
            'movement_date' => now(),
        ]);

        // Update warehouse stock
        $stock = ProductWarehouseStock::firstOrCreate(
            [
                'product_id' => $this->product_id,
                'warehouse_id' => $this->warehouse_id,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
            ]
        );

        $stock->quantity += $this->quantity_produced;
        $stock->available_quantity += $this->quantity_produced;
        $stock->save();
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->quantity_to_produce <= 0) {
            return 0;
        }

        return ($this->quantity_produced / $this->quantity_to_produce) * 100;
    }

    /**
     * Check if order is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->planned_end_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return now()->greaterThan($this->planned_end_date);
    }

    /**
     * Scope for active orders
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for overdue orders
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->where('planned_end_date', '<', now());
    }

    /**
     * Scope for orders assigned to user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for orders created by user
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }
}
