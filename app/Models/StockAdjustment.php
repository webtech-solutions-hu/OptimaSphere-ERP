<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'warehouse_id',
        'product_id',
        'type',
        'reason',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_before',
        'balance_after',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'attachments',
        'adjustment_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'approved_at' => 'datetime',
            'adjustment_date' => 'datetime',
            'attachments' => 'array',
        ];
    }

    /**
     * Boot method to auto-generate reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (empty($adjustment->reference)) {
                $adjustment->reference = self::generateReference();
            }
        });
    }

    /**
     * Generate unique reference number
     */
    private static function generateReference(): string
    {
        $prefix = 'ADJ';
        $date = now()->format('Ymd');
        $lastAdjustment = self::withTrashed()
            ->where('reference', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = intval(substr($lastAdjustment->reference, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Check if adjustment needs approval
     */
    public function needsApproval(): bool
    {
        // Major adjustments (> threshold) need approval
        $threshold = 100; // Configure this value
        return abs($this->quantity) > $threshold;
    }

    /**
     * Approve adjustment
     */
    public function approve(User $approver): bool
    {
        \DB::beginTransaction();

        try {
            // Update adjustment status
            $this->status = 'approved';
            $this->approved_by = $approver->id;
            $this->approved_at = now();
            $this->save();

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

            // Apply the adjustment
            if ($this->type === 'increase') {
                $stock->addStock($this->quantity);
            } else {
                $stock->removeStock($this->quantity);
            }

            // Create stock movement record
            StockMovement::create([
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'type' => 'adjustment',
                'quantity' => $this->type === 'increase' ? $this->quantity : -$this->quantity,
                'unit_cost' => $this->unit_cost,
                'total_cost' => $this->total_cost,
                'balance_before' => $this->balance_before,
                'balance_after' => $this->balance_after,
                'related_document_type' => 'stock_adjustment',
                'related_document_id' => $this->id,
                'created_by' => $approver->id,
                'notes' => "Stock adjustment ({$this->type}) - Reason: {$this->reason}",
                'movement_date' => $this->adjustment_date,
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject adjustment
     */
    public function reject(User $approver, string $reason): bool
    {
        $this->status = 'rejected';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }
}
