<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    protected $fillable = [
        'purchase_requisition_id', 'product_id', 'suggested_supplier_id',
        'quantity_requested', 'current_stock', 'reorder_level',
        'estimated_unit_price', 'estimated_total', 'specification', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'estimated_unit_price' => 'decimal:2',
            'estimated_total' => 'decimal:2',
        ];
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function suggestedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'suggested_supplier_id');
    }
}
