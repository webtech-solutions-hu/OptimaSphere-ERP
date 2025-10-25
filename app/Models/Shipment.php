<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_number', 'sales_order_id', 'warehouse_id', 'status', 'shipment_type',
        'picked_at', 'packed_at', 'shipped_at', 'delivered_at', 'expected_delivery_date',
        'carrier', 'tracking_number', 'shipping_method', 'shipping_cost', 'weight',
        'weight_unit', 'shipping_address', 'picked_by', 'packed_by', 'shipped_by',
        'notes', 'delivery_instructions', 'attachments',
    ];

    protected function casts(): array
    {
        return [
            'picked_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'expected_delivery_date' => 'date',
            'shipping_cost' => 'decimal:2',
            'weight' => 'decimal:2',
            'attachments' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->shipment_number)) {
                $shipment->shipment_number = self::generateShipmentNumber();
            }
        });
    }

    private static function generateShipmentNumber(): string
    {
        $prefix = 'SHP';
        $date = now()->format('Ymd');
        $lastShipment = self::withTrashed()
            ->where('shipment_number', 'like', $prefix . $date . '%')
            ->latest('id')
            ->first();

        $number = $lastShipment ? intval(substr($lastShipment->shipment_number, -4)) + 1 : 1;
        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function packedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function markAsShipped(User $user): bool
    {
        \DB::beginTransaction();

        try {
            $this->status = 'shipped';
            $this->shipped_by = $user->id;
            $this->shipped_at = now();
            $this->save();

            // Create stock movements and reduce inventory
            foreach ($this->items as $item) {
                $stock = ProductWarehouseStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $this->warehouse_id,
                    ],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
                );

                $balanceBefore = $stock->quantity;
                $stock->removeStock($item->quantity);

                StockMovement::create([
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'quantity' => -$item->quantity,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $stock->quantity,
                    'related_document_type' => 'shipment',
                    'related_document_id' => $this->id,
                    'created_by' => $user->id,
                    'notes' => "Shipment {$this->shipment_number}",
                    'movement_date' => now(),
                ]);

                // Update sales order item quantities
                $item->salesOrderItem->quantity_shipped += $item->quantity;
                $item->salesOrderItem->quantity_remaining = $item->salesOrderItem->quantity - $item->salesOrderItem->quantity_shipped;
                $item->salesOrderItem->save();
            }

            // Update sales order status
            $salesOrder = $this->salesOrder;
            if ($salesOrder->isFullyShipped()) {
                $salesOrder->status = 'shipped';
            } else {
                $salesOrder->status = 'partially_shipped';
            }
            $salesOrder->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
