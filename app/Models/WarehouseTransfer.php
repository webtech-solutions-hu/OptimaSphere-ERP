<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'status',
        'requested_date',
        'approved_date',
        'shipped_date',
        'received_date',
        'expected_delivery_date',
        'requested_by',
        'approved_by',
        'shipped_by',
        'received_by',
        'carrier',
        'tracking_number',
        'shipping_cost',
        'notes',
        'rejection_reason',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'requested_date' => 'datetime',
            'approved_date' => 'datetime',
            'shipped_date' => 'datetime',
            'received_date' => 'datetime',
            'expected_delivery_date' => 'date',
            'attachments' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->reference)) {
                $transfer->reference = self::generateReference();
            }
        });
    }

    /**
     * Generate unique reference number
     */
    private static function generateReference(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $lastTransfer = self::withTrashed()
            ->where('reference', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        if ($lastTransfer) {
            $lastNumber = intval(substr($lastTransfer->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Approve transfer
     */
    public function approve(User $approver): bool
    {
        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_date = now();

        return $this->save();
    }

    /**
     * Reject transfer
     */
    public function reject(User $approver, string $reason): bool
    {
        $this->status = 'rejected';
        $this->approved_by = $approver->id;
        $this->approved_date = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Mark as shipped
     */
    public function markAsShipped(User $shipper, ?string $carrier = null, ?string $trackingNumber = null): bool
    {
        \DB::beginTransaction();

        try {
            // Update transfer status
            $this->status = 'in_transit';
            $this->shipped_by = $shipper->id;
            $this->shipped_date = now();
            $this->carrier = $carrier;
            $this->tracking_number = $trackingNumber;
            $this->save();

            // Create stock movement for outbound (from warehouse)
            $fromStock = ProductWarehouseStock::firstOrCreate(
                [
                    'product_id' => $this->product_id,
                    'warehouse_id' => $this->from_warehouse_id,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                ]
            );

            // Get balance before
            $balanceBefore = $fromStock->quantity;

            // Remove stock from source warehouse
            $fromStock->removeStock($this->quantity);

            // Create stock movement record
            StockMovement::create([
                'warehouse_id' => $this->from_warehouse_id,
                'product_id' => $this->product_id,
                'type' => 'transfer_out',
                'quantity' => -$this->quantity,
                'unit_cost' => $this->unit_cost,
                'total_cost' => $this->total_cost,
                'balance_before' => $balanceBefore,
                'balance_after' => $fromStock->quantity,
                'related_document_type' => 'warehouse_transfer',
                'related_document_id' => $this->id,
                'created_by' => $shipper->id,
                'notes' => "Transfer to {$this->toWarehouse->name}",
                'movement_date' => now(),
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark as received
     */
    public function markAsReceived(User $receiver): bool
    {
        \DB::beginTransaction();

        try {
            // Update transfer status
            $this->status = 'received';
            $this->received_by = $receiver->id;
            $this->received_date = now();
            $this->save();

            // Create stock movement for inbound (to warehouse)
            $toStock = ProductWarehouseStock::firstOrCreate(
                [
                    'product_id' => $this->product_id,
                    'warehouse_id' => $this->to_warehouse_id,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                ]
            );

            // Get balance before
            $balanceBefore = $toStock->quantity;

            // Add stock to destination warehouse
            $toStock->addStock($this->quantity);

            // Create stock movement record
            StockMovement::create([
                'warehouse_id' => $this->to_warehouse_id,
                'product_id' => $this->product_id,
                'type' => 'transfer_in',
                'quantity' => $this->quantity,
                'unit_cost' => $this->unit_cost,
                'total_cost' => $this->total_cost,
                'balance_before' => $balanceBefore,
                'balance_after' => $toStock->quantity,
                'related_document_type' => 'warehouse_transfer',
                'related_document_id' => $this->id,
                'created_by' => $receiver->id,
                'notes' => "Transfer from {$this->fromWarehouse->name}",
                'movement_date' => now(),
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
