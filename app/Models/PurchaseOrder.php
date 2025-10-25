<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number', 'supplier_id', 'warehouse_id', 'status', 'order_date',
        'expected_delivery_date', 'actual_delivery_date', 'subtotal', 'tax_amount',
        'shipping_cost', 'discount_amount', 'total_amount', 'delivery_address',
        'delivery_method', 'payment_status', 'payment_terms', 'created_by',
        'approved_by', 'approved_at', 'supplier_reference', 'notes', 'terms_conditions',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->po_number)) {
                $po->po_number = self::generatePONumber();
            }
        });
    }

    private static function generatePONumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $lastPO = self::withTrashed()
            ->where('po_number', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        $number = $lastPO ? intval(substr($lastPO->po_number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'submitted', 'approved', 'sent_to_supplier']);
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(fn($item) => $item->quantity_received >= $item->quantity_ordered);
    }

    public function approve(User $approver): bool
    {
        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        return $this->save();
    }
}
