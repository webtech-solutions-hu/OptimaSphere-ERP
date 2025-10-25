<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceiptNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grn_number', 'purchase_order_id', 'warehouse_id', 'supplier_id', 'status',
        'receipt_type', 'receipt_date', 'verified_at', 'approved_at',
        'supplier_invoice_number', 'supplier_delivery_note', 'supplier_invoice_date',
        'has_discrepancy', 'discrepancy_notes', 'discrepancy_details',
        'quality_status', 'quality_notes', 'received_by', 'verified_by',
        'approved_by', 'notes', 'attachments',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'supplier_invoice_date' => 'date',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'has_discrepancy' => 'boolean',
            'discrepancy_details' => 'array',
            'attachments' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($grn) {
            if (empty($grn->grn_number)) {
                $grn->grn_number = self::generateGRNNumber();
            }
        });
    }

    private static function generateGRNNumber(): string
    {
        $prefix = 'GRN';
        $date = now()->format('Ymd');
        $lastGRN = self::withTrashed()
            ->where('grn_number', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        $number = $lastGRN ? intval(substr($lastGRN->grn_number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptNoteItem::class, 'grn_id');
    }

    /**
     * Verify GRN against PO - User Story 5
     */
    public function verify(User $verifier): bool
    {
        \DB::beginTransaction();

        try {
            $discrepancies = [];
            $hasDiscrepancy = false;

            foreach ($this->items as $item) {
                // Calculate discrepancy
                $item->quantity_discrepancy = $item->quantity_ordered - $item->quantity_received;

                if ($item->quantity_discrepancy != 0) {
                    $hasDiscrepancy = true;
                    $item->discrepancy_type = $item->quantity_discrepancy > 0 ? 'shortage' : 'overage';

                    $discrepancies[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'ordered' => $item->quantity_ordered,
                        'received' => $item->quantity_received,
                        'discrepancy' => $item->quantity_discrepancy,
                        'type' => $item->discrepancy_type,
                    ];
                } else {
                    $item->discrepancy_type = 'match';
                }

                $item->save();
            }

            $this->has_discrepancy = $hasDiscrepancy;
            $this->discrepancy_details = $hasDiscrepancy ? $discrepancies : null;
            $this->verified_by = $verifier->id;
            $this->verified_at = now();
            $this->status = $hasDiscrepancy ? 'discrepancy' : 'verified';
            $this->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve GRN and update stock - User Story 4
     */
    public function approve(User $approver): bool
    {
        \DB::beginTransaction();

        try {
            foreach ($this->items as $item) {
                if ($item->quantity_accepted > 0) {
                    // Get or create warehouse stock
                    $stock = ProductWarehouseStock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'warehouse_id' => $this->warehouse_id,
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'available_quantity' => 0,
                        ]
                    );

                    $balanceBefore = $stock->quantity;

                    // Add stock
                    $stock->addStock($item->quantity_accepted);

                    // Update storage location if provided
                    if ($item->storage_location) {
                        $stock->aisle = $item->storage_location;
                        $stock->save();
                    }

                    // Create stock movement
                    StockMovement::create([
                        'warehouse_id' => $this->warehouse_id,
                        'product_id' => $item->product_id,
                        'type' => 'in',
                        'quantity' => $item->quantity_accepted,
                        'unit_cost' => $item->purchaseOrderItem->unit_price,
                        'total_cost' => $item->quantity_accepted * $item->purchaseOrderItem->unit_price,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $stock->quantity,
                        'related_document_type' => 'goods_receipt_note',
                        'related_document_id' => $this->id,
                        'created_by' => $approver->id,
                        'notes' => "GRN {$this->grn_number}" . ($item->batch_number ? " - Batch: {$item->batch_number}" : ''),
                        'movement_date' => now(),
                    ]);

                    // Update PO item quantities
                    $poItem = $item->purchaseOrderItem;
                    $poItem->quantity_received += $item->quantity_accepted;
                    $poItem->quantity_remaining = $poItem->quantity_ordered - $poItem->quantity_received;
                    $poItem->save();
                }
            }

            // Update PO status
            $po = $this->purchaseOrder;
            if ($po->isFullyReceived()) {
                $po->status = 'received';
                $po->actual_delivery_date = $this->receipt_date;
            } else {
                $po->status = 'partially_received';
            }
            $po->save();

            // Update GRN status
            $this->status = 'approved';
            $this->approved_by = $approver->id;
            $this->approved_at = now();
            $this->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'verified', 'discrepancy']);
    }
}
