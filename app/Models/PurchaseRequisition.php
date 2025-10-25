<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pr_number', 'warehouse_id', 'requisition_type', 'priority', 'status',
        'requisition_date', 'required_by_date', 'approved_at', 'requested_by',
        'approved_by', 'purchase_order_id', 'converted_at', 'purpose',
        'justification', 'notes', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'requisition_date' => 'date',
            'required_by_date' => 'date',
            'approved_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pr) {
            if (empty($pr->pr_number)) {
                $pr->pr_number = self::generatePRNumber();
            }
        });
    }

    private static function generatePRNumber(): string
    {
        $prefix = 'PR';
        $date = now()->format('Ymd');
        $lastPR = self::withTrashed()
            ->where('pr_number', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        $number = $lastPR ? intval(substr($lastPR->pr_number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function approve(User $approver): bool
    {
        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        return $this->save();
    }

    public function reject(User $approver, string $reason): bool
    {
        $this->status = 'rejected';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        return $this->save();
    }

    /**
     * Convert PR to PO
     */
    public function convertToPurchaseOrder(User $user): ?PurchaseOrder
    {
        if ($this->status !== 'approved') {
            return null;
        }

        \DB::beginTransaction();

        try {
            // Group items by suggested supplier
            $itemsBySupplier = $this->items->groupBy('suggested_supplier_id');

            $purchaseOrders = [];

            foreach ($itemsBySupplier as $supplierId => $items) {
                $po = PurchaseOrder::create([
                    'supplier_id' => $supplierId ?? $items->first()->product->primarySupplier->id,
                    'warehouse_id' => $this->warehouse_id,
                    'status' => 'draft',
                    'order_date' => now(),
                    'expected_delivery_date' => $this->required_by_date,
                    'created_by' => $user->id,
                    'notes' => "Created from PR {$this->pr_number}",
                ]);

                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item->product_id,
                        'quantity_ordered' => $item->quantity_requested,
                        'quantity_remaining' => $item->quantity_requested,
                        'unit_price' => $item->estimated_unit_price ?? $item->product->cost_price,
                        'line_total' => $item->quantity_requested * ($item->estimated_unit_price ?? $item->product->cost_price),
                    ]);
                }

                $purchaseOrders[] = $po;
            }

            // Update PR status
            $this->status = 'converted_to_po';
            $this->purchase_order_id = $purchaseOrders[0]->id; // Link to first PO
            $this->converted_at = now();
            $this->save();

            \DB::commit();
            return $purchaseOrders[0];
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Auto-generate PR for low stock products
     */
    public static function generateForLowStock(Warehouse $warehouse): ?self
    {
        $lowStockProducts = Product::where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->where('current_stock', '>', 0)
            ->get();

        if ($lowStockProducts->isEmpty()) {
            return null;
        }

        \DB::beginTransaction();

        try {
            $pr = self::create([
                'warehouse_id' => $warehouse->id,
                'requisition_type' => 'auto_reorder',
                'priority' => 'medium',
                'status' => 'draft',
                'requisition_date' => now(),
                'required_by_date' => now()->addDays(7),
                'requested_by' => 1, // System user
                'purpose' => 'Auto-generated reorder for low stock items',
            ]);

            foreach ($lowStockProducts as $product) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $pr->id,
                    'product_id' => $product->id,
                    'suggested_supplier_id' => $product->primary_supplier_id,
                    'quantity_requested' => $product->reorder_quantity ?? ($product->reorder_level * 2),
                    'current_stock' => $product->current_stock,
                    'reorder_level' => $product->reorder_level,
                    'estimated_unit_price' => $product->cost_price,
                    'estimated_total' => ($product->reorder_quantity ?? ($product->reorder_level * 2)) * $product->cost_price,
                ]);
            }

            \DB::commit();
            return $pr;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }
}
