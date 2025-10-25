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
        $this->status = 'in_transit';
        $this->shipped_by = $shipper->id;
        $this->shipped_date = now();
        $this->carrier = $carrier;
        $this->tracking_number = $trackingNumber;

        return $this->save();
    }

    /**
     * Mark as received
     */
    public function markAsReceived(User $receiver): bool
    {
        $this->status = 'received';
        $this->received_by = $receiver->id;
        $this->received_date = now();

        return $this->save();
    }
}
