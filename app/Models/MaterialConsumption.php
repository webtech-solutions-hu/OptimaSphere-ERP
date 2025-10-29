<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialConsumption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'production_order_id',
        'production_order_operation_id',
        'material_requisition_id',
        'product_id',
        'warehouse_id',
        'quantity_consumed',
        'unit_id',
        'unit_cost',
        'total_cost',
        'consumption_type',
        'batch_id',
        'batch_number',
        'lot_number',
        'serial_number',
        'consumed_at',
        'consumed_by',
        'notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'quantity_consumed' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'consumed_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($consumption) {
            if (empty($consumption->reference)) {
                $consumption->reference = self::generateReference();
            }
        });
    }

    private static function generateReference(): string
    {
        $prefix = 'MC';
        $date = now()->format('Ymd');

        $last = self::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->latest('id')
            ->first();

        $number = $last ? intval(substr($last->reference, -4)) + 1 : 1;
        return "{$prefix}-{$date}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function productionOrderOperation(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderOperation::class);
    }

    public function materialRequisition(): BelongsTo
    {
        return $this->belongsTo(MaterialRequisition::class);
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumed_by');
    }
}
