<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number', 'customer_id', 'warehouse_id', 'status', 'order_date',
        'required_date', 'shipped_date', 'delivered_date', 'subtotal', 'tax_amount',
        'shipping_cost', 'discount_amount', 'total_amount', 'shipping_address',
        'shipping_method', 'tracking_number', 'carrier', 'payment_status',
        'payment_method', 'sales_rep_id', 'customer_po_number', 'notes', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'required_date' => 'date',
            'shipped_date' => 'date',
            'delivered_date' => 'date',
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

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    private static function generateOrderNumber(): string
    {
        $prefix = 'SO';
        $date = now()->format('Ymd');
        $lastOrder = self::withTrashed()
            ->where('order_number', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        $number = $lastOrder ? intval(substr($lastOrder->order_number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnModel::class, 'original_document_id')
            ->where('original_document_type', 'sales_order');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'confirmed', 'processing']);
    }

    public function isFullyShipped(): bool
    {
        return $this->items->every(fn($item) => $item->quantity_shipped >= $item->quantity);
    }
}
