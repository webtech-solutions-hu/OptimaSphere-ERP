<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPick extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_requisition_item_id',
        'product_id',
        'warehouse_id',
        'batch_id',
        'batch_number',
        'lot_number',
        'serial_number',
        'quantity_picked',
        'unit_id',
        'location',
        'picked_at',
        'picked_by',
        'status',
        'verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_picked' => 'decimal:4',
            'picked_at' => 'datetime',
            'verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function materialRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(MaterialRequisitionItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
