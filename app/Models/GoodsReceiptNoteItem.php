<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptNoteItem extends Model
{
    protected $fillable = [
        'grn_id', 'purchase_order_item_id', 'product_id', 'quantity_ordered',
        'quantity_received', 'quantity_accepted', 'quantity_rejected',
        'quantity_discrepancy', 'discrepancy_type', 'discrepancy_reason',
        'batch_number', 'serial_numbers', 'manufacturing_date', 'expiry_date',
        'condition', 'storage_location', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:2',
            'quantity_received' => 'decimal:2',
            'quantity_accepted' => 'decimal:2',
            'quantity_rejected' => 'decimal:2',
            'quantity_discrepancy' => 'decimal:2',
            'serial_numbers' => 'array',
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
