<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequisitionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_requisition_id',
        'production_order_item_id',
        'product_id',
        'warehouse_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_picked',
        'quantity_issued',
        'unit_id',
        'status',
        'storage_location',
        'requires_batch_tracking',
        'picking_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_approved' => 'decimal:4',
            'quantity_picked' => 'decimal:4',
            'quantity_issued' => 'decimal:4',
            'requires_batch_tracking' => 'boolean',
        ];
    }

    public function materialRequisition(): BelongsTo
    {
        return $this->belongsTo(MaterialRequisition::class);
    }

    public function productionOrderItem(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
